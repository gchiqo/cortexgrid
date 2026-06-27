<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\ConfigSuggester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigSuggestionController extends Controller
{
    public function generate(Request $request, ConfigSuggester $suggester): View|RedirectResponse
    {
        $dataset = Dataset::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($request->integer('dataset_id'));

        $result = $suggester->suggest($dataset->id, 3);

        if (empty($result['configs'])) {
            return redirect("/dashboard/datasets/{$dataset->id}")->withErrors([
                'suggest' => 'ჯერ ატვირთე მონაცემები ამ დატასეტში, რომ ჩატბოტი მათ მიხედვით დაგენერირდეს.',
            ]);
        }

        return view('configs.suggestions', $result + ['dataset' => $dataset]);
    }
}
