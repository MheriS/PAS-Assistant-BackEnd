<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KeycloakMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $keycloakBaseUrl = config('services.keycloak.base_url', 'http://localhost:8180');
        $realm           = config('services.keycloak.realm', 'pas-assistant');

        $certsUrl = "{$keycloakBaseUrl}/realms/{$realm}/protocol/openid-connect/certs";

        try {
            // Check cache for Keycloak's JWKS to avoid HTTP round trips per request
            $jwks = \Illuminate\Support\Facades\Cache::remember('keycloak_jwks', 86400, function () use ($certsUrl) {
                $response = Http::timeout(5)->get($certsUrl);
                if ($response->failed()) {
                    throw new \Exception('Failed to fetch Keycloak public keys');
                }
                return $response->json();
            });

            $keys = \Firebase\JWT\JWK::parseKeySet($jwks);
            $decoded = \Firebase\JWT\JWT::decode($token, $keys);
            
            $request->attributes->set('keycloak_user', (array) $decoded);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['message' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid Token: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}