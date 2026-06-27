<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\KnowledgeProfiler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Knowledge Explorer — shows what the platform understood about a dataset.
 */
class ExplorerController extends Controller
{
    public function show(Request $request, Dataset $dataset, KnowledgeProfiler $profiler): View
    {
        $this->authorizeOwner($request, $dataset);

        return view('explorer', ['dataset' => $dataset] + $profiler->facets($dataset->id));
    }

    public function analyze(Request $request, Dataset $dataset, KnowledgeProfiler $profiler): JsonResponse
    {
        $this->authorizeOwner($request, $dataset);

        return response()->json($profiler->analyze($dataset->id));
    }

    private function authorizeOwner(Request $request, Dataset $dataset): void
    {
        abort_unless($dataset->tenant_id === $request->user()->tenant_id, 403);
    }
}
