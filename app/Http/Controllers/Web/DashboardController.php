<?php

namespace App\Http\Controllers\Web;

use App\Actions\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use App\Models\ApiKey;
use App\Models\UsageEvent;
use App\Services\Rag\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ProvisionTenant $provision): View
    {
        $user = $request->user();

        if (! $user->tenant_id) {
            $secret = $provision->forUser($user->refresh());
            if ($secret) {
                $request->session()->flash('new_api_key', $secret);
            }
            $user->refresh();
        }

        $tenant = $user->tenant;

        $usage = [
            'ingest' => (int) UsageEvent::where('tenant_id', $tenant->id)->where('kind', 'ingest')->sum('qty'),
            'query' => (int) UsageEvent::where('tenant_id', $tenant->id)->where('kind', 'query')->sum('qty'),
            'tokens' => (int) UsageEvent::where('tenant_id', $tenant->id)->where('kind', 'tokens')->sum('qty'),
        ];

        return view('dashboard', [
            'user' => $user,
            'tenant' => $tenant,
            'apiKeys' => $tenant->apiKeys()->orderByDesc('id')->get(),
            'configs' => $tenant->aiConfigs()->orderBy('id')->get(),
            'sources' => $tenant->sources()->orderByDesc('id')->limit(20)->get(),
            'usage' => $usage,
        ]);
    }

    public function issueKey(Request $request): RedirectResponse
    {
        [, $secret] = ApiKey::issue($request->user()->tenant, $request->string('label')->toString() ?: null);

        return back()->with('new_api_key', $secret);
    }

    public function revokeKey(Request $request, ApiKey $apiKey): RedirectResponse
    {
        abort_unless($apiKey->tenant_id === $request->user()->tenant_id, 403);

        $apiKey->forceFill(['revoked_at' => now()])->save();

        return back()->with('status', 'გასაღები გაუქმდა.');
    }

    public function ask(Request $request, AskService $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'config_id' => ['nullable', 'integer'],
        ]);

        $tenant = $request->user()->tenant;

        $config = isset($data['config_id'])
            ? AiConfig::where('tenant_id', $tenant->id)->find($data['config_id'])
            : AiConfig::where('tenant_id', $tenant->id)->orderBy('id')->first();

        return response()->json($ask->answer($tenant->id, $data['question'], $config));
    }
}
