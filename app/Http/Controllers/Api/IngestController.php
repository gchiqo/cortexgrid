<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\Ingest\IngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /v1/ingest — generic ingestion (also used by the WordPress plugin).
 *
 * Accepts either:
 *   { "text": "...", "title": "...", "source_name": "...", "type": "pdf|api|...", "dataset": "Store" }
 * or:
 *   { "records": [ { "title": "...", "text": "...", ...fields } ], "source_name": "...", "dataset": 3 }
 *
 * `dataset` is optional: a dataset id, or a name (find-or-create). Defaults to the tenant's default dataset.
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
            'dataset' => ['nullable'],
        ]);

        $datasetId = $this->resolveDataset($tenantId, $data['dataset'] ?? null);

        $records = $request->filled('records')
            ? $data['records']
            : [['title' => $data['title'] ?? null, 'text' => $data['text']]];

        $type = $data['type'] ?? ($request->filled('records') ? 'api' : 'text');
        $name = $data['source_name'] ?? 'Ingest '.now()->format('Y-m-d H:i');

        $summary = $ingest->ingest($tenantId, $datasetId, $type, $name, $records, $apiKeyId);

        return response()->json($summary + [
            'dataset_id' => $datasetId,
            'status' => 'processing',
            'message' => 'ჩაიტვირთა; ემბედინგები მუშავდება ფონურად.',
        ], 201);
    }

    /** Resolve `dataset` (id | name | null) to a dataset id within the tenant. */
    private function resolveDataset(int $tenantId, mixed $dataset): int
    {
        if (is_numeric($dataset)) {
            $found = Dataset::where('tenant_id', $tenantId)->find((int) $dataset);
            if ($found) {
                return $found->id;
            }
        }

        if (is_string($dataset) && trim($dataset) !== '') {
            return Dataset::firstOrCreate(['tenant_id' => $tenantId, 'name' => trim($dataset)])->id;
        }

        return Dataset::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'ჩემი მონაცემები'])->id;
    }
}
