<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatasetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $dataset = Dataset::create($data + ['tenant_id' => $request->user()->tenant_id]);

        return redirect("/dashboard/datasets/{$dataset->id}")->with('status', 'დატასეტი შეიქმნა.');
    }

    public function show(Request $request, Dataset $dataset): View
    {
        $this->authorizeOwner($request, $dataset);

        return view('dataset', [
            'dataset' => $dataset,
            'sources' => $dataset->sources()->latest()->limit(50)->get(),
            'configs' => $dataset->aiConfigs()->orderBy('id')->get(),
            'docCount' => $dataset->documents()->count(),
            'chunkCount' => $dataset->chunks()->count(),
        ]);
    }

    public function destroy(Request $request, Dataset $dataset): RedirectResponse
    {
        $this->authorizeOwner($request, $dataset);

        $dataset->sources()->delete();    // cascades documents + chunks at the DB level
        $dataset->aiConfigs()->delete();  // cascades conversations + messages
        $dataset->delete();

        return redirect('/dashboard')->with('status', 'დატასეტი წაიშალა.');
    }

    /** Poll target for the upload animation. */
    public function sourceStatus(Request $request, Source $source): JsonResponse
    {
        abort_unless($source->tenant_id === $request->user()->tenant_id, 403);

        return response()->json(['status' => $source->status]);
    }

    private function authorizeOwner(Request $request, Dataset $dataset): void
    {
        abort_unless($dataset->tenant_id === $request->user()->tenant_id, 403);
    }
}
