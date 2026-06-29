<?php

namespace Binaryk\LaravelRestify\Http\Controllers\Auth\Social;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns the provider authorization URL the client should redirect the user to.
 *
 * For the SPA / API-token flow this responds with JSON ({ url }) so the frontend
 * can perform the redirect itself. Append `?redirect=1` to be 302-redirected
 * straight to the provider instead.
 */
class SocialRedirectController extends Controller
{
    public function __invoke(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        $driver = Socialite::driver($provider);

        if (config('restify.auth.social.stateless', true) && method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }

        if ($scopes = config("restify.auth.social.providers.$provider.scopes")) {
            $driver = $driver->scopes($scopes);
        }

        $redirect = $driver->redirect();

        if ($request->boolean('redirect')) {
            return $redirect;
        }

        return new JsonResponse([
            'provider' => $provider,
            'url' => $redirect->getTargetUrl(),
        ]);
    }
}
