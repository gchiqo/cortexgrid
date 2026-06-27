<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use App\Services\Rag\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Glass-box test console: chat on the right, live pipeline trace on the left.
 */
class ConsoleController extends Controller
{
    public function index(Request $request): View
    {
        $configs = AiConfig::where('tenant_id', $request->user()->tenant_id)->orderBy('id')->get();

        return view('console', compact('configs'));
    }

    public function ask(Request $request, AskService $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'config_id' => ['nullable', 'integer'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string'],
        ]);

        $tenant = $request->user()->tenant;

        $config = isset($data['config_id'])
            ? AiConfig::where('tenant_id', $tenant->id)->find($data['config_id'])
            : AiConfig::where('tenant_id', $tenant->id)->orderBy('id')->first();

        $result = $ask->answer(
            $tenant->id,
            $data['question'],
            $config,
            6,
            null,
            $data['history'] ?? [],
            withTrace: true,
        );

        return response()->json($result);
    }
}
