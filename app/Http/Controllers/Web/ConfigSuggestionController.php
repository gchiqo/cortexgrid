<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ConfigSuggester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfigSuggestionController extends Controller
{
    public function generate(Request $request, ConfigSuggester $suggester): View|RedirectResponse
    {
        $result = $suggester->suggest((int) $request->user()->tenant_id, 3);

        if (empty($result['configs'])) {
            return redirect('/dashboard')->withErrors([
                'suggest' => 'ჯერ ატვირთე მონაცემები, რომ კონფიგურაცია მათ მიხედვით დაგენერირდეს.',
            ]);
        }

        return view('configs.suggestions', $result);
    }
}
