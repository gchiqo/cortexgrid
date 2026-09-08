<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\EmbedChunks;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function reprocess(Request $request, Source $source): RedirectResponse
    {
        $this->authorizeOwner($request, $source);

        $source->update(['status' => 'processing']);
        foreach ($source->documents()->pluck('id') as $documentId) {
            EmbedChunks::dispatch($documentId);
        }

        return back()->with('status', __('messages.source_reprocessing', ['name' => $source->name]));
    }

    public function destroy(Request $request, Source $source): RedirectResponse
    {
        $this->authorizeOwner($request, $source);
        $name = $source->name;
        $source->delete(); // cascades documents + chunks

        return back()->with('status', __('messages.source_deleted', ['name' => $name]));
    }

    private function authorizeOwner(Request $request, Source $source): void
    {
        abort_unless($source->tenant_id === $request->user()->tenant_id, 403);
    }
}
