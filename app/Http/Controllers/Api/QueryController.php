<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use App\Services\Rag\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /v1/query — answer a question in Georgian, grounded in the tenant's data, with citations.
 *
 *   { "question": "...", "config_id": 1, "k": 6 }
 */
class QueryController extends Controller
{
    public function store(Request $request, AskService $ask): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $apiKeyId = optional($request->attributes->get('api_key'))->id;

        $data = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'config_id' => ['nullable', 'integer'],
            'k' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $config = isset($data['config_id'])
            ? AiConfig::where('tenant_id', $tenantId)->find($data['config_id'])
            : AiConfig::where('tenant_id', $tenantId)->orderBy('id')->first();

        $result = $ask->answer($tenantId, $data['question'], $config, $data['k'] ?? 6, $apiKeyId);

        return response()->json($result);
    }
}
