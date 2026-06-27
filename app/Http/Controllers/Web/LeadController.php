<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $leads = Lead::where('tenant_id', $request->user()->tenant_id)
            ->with('config')
            ->latest()
            ->limit(200)
            ->get();

        return view('leads', compact('leads'));
    }
}
