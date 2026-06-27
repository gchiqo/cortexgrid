<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Flitt payments — buy credit packs. Pattern adapted from a working Flitt integration:
 * checkout/url → redirect → server callback (approved) adds credits.
 */
class FlittPaymentController extends Controller
{
    /** Start a payment for a credit pack. */
    public function buy(Request $request): RedirectResponse
    {
        $packs = config('services.flitt.packs');
        $index = (int) $request->input('pack');
        if (! isset($packs[$index])) {
            return back()->withErrors(['pay' => 'არასწორი პაკეტი.']);
        }
        $pack = $packs[$index];
        $tenant = $request->user()->tenant;

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'amount_gel' => $pack['gel'],
            'credits' => $pack['credits'],
            'status' => 'pending',
        ]);

        $params = [
            'server_callback_url' => route('flitt.callback'),
            'response_url' => route('flitt.response'),
            'order_id' => (string) $payment->id,
            'currency' => 'GEL',
            'order_desc' => number_format($pack['credits']).' კრედიტი — GTUH AI',
            'amount' => (int) round($pack['gel'] * 100),
        ];

        try {
            $response = Http::asJson()->timeout(20)->post(config('services.flitt.checkout_url'), [
                'request' => array_merge($params, [
                    'merchant_id' => (int) config('services.flitt.merchant_id'),
                    'signature' => $this->sign($params),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Flitt checkout failed', ['error' => $e->getMessage()]);
            $payment->update(['status' => 'failed']);

            return back()->withErrors(['pay' => 'Flitt-თან კავშირი ვერ მოხერხდა.']);
        }

        $checkoutUrl = data_get($response->json(), 'response.checkout_url');
        if (! $checkoutUrl) {
            $payment->update(['status' => 'failed', 'gateway_response' => $response->json()]);

            return back()->withErrors(['pay' => 'გადახდის გვერდი ვერ შეიქმნა.']);
        }

        return redirect()->away($checkoutUrl);
    }

    /** Server-to-server callback (authoritative). */
    public function callback(Request $request): JsonResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            $allowed = config('services.flitt.allowed_ips', []);
            if (! in_array($request->ip(), $allowed, true)) {
                return response()->json(['error' => 'forbidden'], 403);
            }
        }

        $data = $request->isJson() ? $request->json()->all() : $request->all();
        $payment = Payment::find(data_get($data, 'order_id'));
        if (! $payment) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $this->applyStatus($payment, strtolower((string) data_get($data, 'order_status')), $data);

        return response()->json(['message' => 'ok']);
    }

    /** Front-channel return (browser). */
    public function response(Request $request): RedirectResponse
    {
        $data = $request->all();
        $payment = Payment::find(data_get($data, 'order_id'));
        if ($payment) {
            $this->applyStatus($payment, strtolower((string) data_get($data, 'order_status')), $data);
        }

        if ($payment && $payment->fresh()->status === 'completed') {
            return redirect('/dashboard/billing')->with('status', '✓ გადახდა წარმატებულია — კრედიტები დაემატა.');
        }

        return redirect('/dashboard/billing')->withErrors(['pay' => 'გადახდა ვერ შესრულდა ან გაუქმდა.']);
    }

    /** Idempotently apply a gateway status — adds credits once on first approval. */
    private function applyStatus(Payment $payment, string $status, array $data): void
    {
        if ($payment->status === 'completed') {
            return; // already credited (dedup)
        }

        $payment->update(['gateway_response' => $data]);

        if ($status === 'approved') {
            $payment->update(['status' => 'completed']);
            Tenant::where('id', $payment->tenant_id)->increment('credits', $payment->credits);
        } elseif ($status !== '') {
            $payment->update(['status' => 'failed']);
        }
    }

    /** Flitt signature: sha1(secret | sorted param values). */
    private function sign(array $params): string
    {
        $params['merchant_id'] = config('services.flitt.merchant_id');
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        ksort($params);
        $values = array_values($params);
        array_unshift($values, config('services.flitt.secret_key'));

        return sha1(implode('|', $values));
    }
}
