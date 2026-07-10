<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MicrosoftSsoService
{
    private const AUTHORITY = 'https://login.microsoftonline.com';
    private const GRAPH_ME  = 'https://graph.microsoft.com/v1.0/me';

    /** Build the Entra ID authorization URL the browser should be redirected to. */
    public function getAuthorizationUrl(string $state): string
    {
        $tenant = config('services.microsoft.tenant_id');

        $params = [
            'client_id'     => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri'  => config('services.microsoft.redirect'),
            'response_mode' => 'query',
            'scope'         => 'openid profile email User.Read',
            'state'         => $state,
        ];

        return self::AUTHORITY . "/{$tenant}/oauth2/v2.0/authorize?" . http_build_query($params);
    }

    /** Exchange an authorization code for an access token. Returns null on failure. */
    public function exchangeCodeForToken(string $code): ?array
    {
        $tenant = config('services.microsoft.tenant_id');

        try {
            $response = Http::asForm()->post(self::AUTHORITY . "/{$tenant}/oauth2/v2.0/token", [
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'redirect_uri'  => config('services.microsoft.redirect'),
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'scope'         => 'openid profile email User.Read',
            ]);

            if (!$response->successful()) {
                Log::warning('Microsoft SSO token exchange failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Microsoft SSO token exchange threw an exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /** Fetch the signed-in user's profile from Microsoft Graph. Returns null on failure. */
    public function getUserProfile(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)->get(self::GRAPH_ME);

            if (!$response->successful()) {
                Log::warning('Microsoft Graph /me request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Microsoft Graph /me request threw an exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Resolve a Graph profile to a local user. Pure logic — no HTTP — so it can be
     * exercised directly (e.g. via tinker) with a fake profile array.
     *
     * Returns ['status' => 'ok'|'wrong_domain'|'not_found'|'blocked', 'email' => string, 'user' => ?User].
     */
    public function resolveLocalUser(array $profile): array
    {
        $email = $profile['mail'] ?? $profile['userPrincipalName'] ?? '';
        $email = mb_strtolower(trim($email));

        $allowedDomain = mb_strtolower(config('services.microsoft.allowed_domain'));

        if ($email === '' || !Str::endsWith($email, '@' . $allowedDomain)) {
            return ['status' => 'wrong_domain', 'email' => $email, 'user' => null];
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return ['status' => 'not_found', 'email' => $email, 'user' => null];
        }

        if ($user->is_blocked) {
            return ['status' => 'blocked', 'email' => $email, 'user' => $user];
        }

        return ['status' => 'ok', 'email' => $email, 'user' => $user];
    }
}
