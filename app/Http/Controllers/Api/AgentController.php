<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /v1/agents — list this tenant's widget-enabled agents with their embed snippets.
 * Used by integrations (e.g. the WordPress plugin) to let the user pick a widget.
 */
class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $base = rtrim(url('/'), '/');

        $agents = AiConfig::where('tenant_id', $tenantId)
            ->where('widget_enabled', true)
            ->orderBy('id')
            ->get(['id', 'name', 'public_key'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'public_key' => $a->public_key,
                'embed' => '<script src="'.$base.'/embed.js?key='.$a->public_key.'" async></script>',
            ]);

        return response()->json(['agents' => $agents]);
    }
}
