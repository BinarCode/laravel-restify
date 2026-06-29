<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth\Social;

use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Handles the OAuth callback: exchanges the provider code for a user, resolves
 * (find-or-create + link) the application user, and issues a Sanctum token.
 *
 * By default it responds with the user + token (matching `restifyAuth` login).
 * If `restify.auth.social.redirect_url` is set, it redirects there instead with
 * the token / error injected via placeholders.
 */
class SocialCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider)
    {
        $driver = Socialite::driver($provider);

        if (config('restify.auth.social.stateless', true) && method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }

        try {
            $socialiteUser = $driver->user();
        } catch (\Throwable $e) {
            return $this->failed($provider, 'Unable to authenticate with '.$provider.'.');
        }

        $user = Restify::resolveSocialUser($provider, $socialiteUser, $request);

        Auth::login($user);

        $tokenTtl = config('restify.auth.token_ttl');
        $expiresAt = $tokenTtl ? now()->addMinutes($tokenTtl) : null;

        $token = $user->createToken(
            config('restify.auth.social.token_name', 'social'),
            ['*'],
            $expiresAt
        );

        return $this->success($provider, $token->plainTextToken, $tokenTtl, $user);
    }

    protected function success(string $provider, string $token, ?int $tokenTtl, $user)
    {
        if ($redirectUrl = config('restify.auth.social.redirect_url')) {
            return redirect()->away(strtr($redirectUrl, [
                '{token}' => $token,
                '{provider}' => $provider,
                '{error}' => '',
            ]));
        }

        return rest($user)->indexMeta([
            'token' => $token,
            'expires_in' => $tokenTtl ? $tokenTtl * 60 : null,
            'provider' => $provider,
        ]);
    }

    protected function failed(string $provider, string $message)
    {
        if ($redirectUrl = config('restify.auth.social.redirect_url')) {
            return redirect()->away(strtr($redirectUrl, [
                '{token}' => '',
                '{provider}' => $provider,
                '{error}' => rawurlencode($message),
            ]));
        }

        return new JsonResponse(['message' => $message], 401);
    }
}
