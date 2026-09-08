<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Locales the UI is translated into, in the order the switcher cycles them. */
    public const SUPPORTED = ['ka', 'en'];

    /**
     * Apply the visitor's chosen UI language for this request.
     *
     * The choice lives in the session, so it survives navigation without
     * putting a locale segment on every route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
