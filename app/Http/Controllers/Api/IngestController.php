<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ingest\IngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /v1/ingest — generic ingestion (also used by the WordPress plugin).
 *
 * Accepts either:
 *   { "text": "...", "title": "...", "source_name": "...", "type": "pdf|api|..." }
 * or:
 *   { "records": [ { "title": "...", "text": "...", ...fields } ], "source_name": "...", "type": "wordpress" }
 */
class IngestController extends Controller
{
    public function store(Request $request, IngestService $ingest): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $apiKeyId = optional($request->attributes->get('api_key'))->id;

        $data = $request->validate([
            'text' => ['required_without:records', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'records' => ['required_without:text', 'array'],
            'records.*' => ['array'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $records = $request->filled('records')
            ? $data['records']
            : [['title' => $data['title'] ?? null, 'text' => $data['text']]];

        $type = $data['type'] ?? ($request->filled('records') ? 'api' : 'text');
        $name = $data['source_name'] ?? 'Ingest '.now()->format('Y-m-d H:i');

        $summary = $ingest->ingest($tenantId, $type, $name, $records, $apiKeyId);

        return response()->json($summary + [
            'status' => 'processing',
            'message' => 'ჩაიტვირთა; ემბედინგები მუშავდება ფონურად.',
        ], 201);
    }
}
