<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function index(Request $request): View
    {
        return view('docs', ['base' => rtrim(url('/'), '/')]);
    }
}
