<?php

namespace App\Http\Controllers\Web;

use App\Actions\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(ProvisionTenant $provision): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('/login')->withErrors(['email' => 'Google ავტორიზაცია ვერ მოხერხდა.']);
        }

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);

        if (! $user->exists) {
            $user->name = $googleUser->getName() ?: $googleUser->getNickname() ?: 'User';
            $user->password = bcrypt(Str::random(40));
            $user->email_verified_at = now();
            $user->save();
        }

        $apiKey = $provision->forUser($user);

        Auth::login($user, remember: true);

        return redirect('/dashboard')->with('new_api_key', $apiKey);
    }
}
