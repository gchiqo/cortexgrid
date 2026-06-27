<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        return view('billing', [
            'credits' => (int) $tenant->credits,
            'packs' => config('services.flitt.packs'),
            'payments' => $tenant->payments()->latest()->limit(20)->get(),
        ]);
    }
}
