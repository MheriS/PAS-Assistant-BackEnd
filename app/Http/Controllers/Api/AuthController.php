<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login via username & password (SQL / Laravel Auth).
     *
     * Supports both bcrypt-hashed passwords and plain-text passwords
     * stored directly in PostgreSQL (e.g. via pgAdmin).
     * If a plain-text password match is found, it is automatically
     * upgraded to bcrypt for future logins.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // --- Attempt 1: standard Laravel Auth (bcrypt-hashed password) ---
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            /** @var User $user */
            $user = Auth::user();
            $token = $user->createToken('petugas-token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login successful',
                'user'    => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
                'token'    => $token,
                'is_admin' => true,
            ]);
        }

        // --- Attempt 2: plain-text password fallback (pgAdmin / direct SQL insert) ---
        $user = User::where('username', $credentials['username'])->first();

        if ($user) {
            // Read the raw password value from DB (bypass the 'hashed' cast)
            $storedPassword = $user->getRawOriginal('password') ?? $user->getAttributes()['password'] ?? null;

            // Check if the stored password is NOT a bcrypt hash and matches plain text
            if ($storedPassword && !str_starts_with($storedPassword, '$2y$') && !str_starts_with($storedPassword, '$2a$') && !str_starts_with($storedPassword, '$2b$')) {
                // Plain-text comparison
                if ($storedPassword === $credentials['password']) {
                    // Auto-upgrade: hash the plain-text password to bcrypt
                    // Use DB::table to avoid the 'hashed' cast double-hashing
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['password' => Hash::make($credentials['password'])]);

                    $token = $user->createToken('petugas-token')->plainTextToken;

                    return response()->json([
                        'status'  => 'success',
                        'message' => 'Login successful',
                        'user'    => [
                            'id'       => $user->id,
                            'name'     => $user->name,
                            'username' => $user->username,
                            'email'    => $user->email,
                        ],
                        'token'    => $token,
                        'is_admin' => true,
                    ]);
                }
            }
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Username atau password tidak valid',
        ], 401);
    }

    /**
     * Login via Keycloak SSO token.
     * Frontend sends the Keycloak access_token, backend validates it
     * against the Keycloak userinfo endpoint, then finds or creates a
     * local user and returns a Sanctum token.
     */
    public function loginSSO(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $kcToken = $request->input('token');

        // Keycloak base URL — same one used by the middleware
        $keycloakBaseUrl = config('services.keycloak.base_url', 'http://localhost:8180');
        $realm           = config('services.keycloak.realm', 'pas-assistant');

        $certsUrl = "{$keycloakBaseUrl}/realms/{$realm}/protocol/openid-connect/certs";

        try {
            // First decode payload without verification to check expiration easily or log
            $tokenParts = explode('.', $kcToken);
            if (count($tokenParts) !== 3) {
                throw new \Exception("Invalid token format");
            }
            
            // Get public keys from Keycloak to verify signature
            $jwksResponse = \Illuminate\Support\Facades\Http::timeout(10)->get($certsUrl);
            if ($jwksResponse->failed()) {
                throw new \Exception("Could not fetch Keycloak public keys");
            }
            
            $jwks = $jwksResponse->json();
            $keys = \Firebase\JWT\JWK::parseKeySet($jwks);

            // Verify and decode Token - this acts as a robust substitute for userinfo
            $decoded = \Firebase\JWT\JWT::decode($kcToken, $keys);
            // $decoded is an object (stdClass)
            $kcUser = (array) $decoded;


            // Map Keycloak claims to local user fields
            $username = $kcUser['preferred_username'] ?? $kcUser['sub'] ?? null;
            $email    = $kcUser['email'] ?? "{$username}@keycloak.local";
            $name     = $kcUser['name']
                        ?? trim(($kcUser['given_name'] ?? '') . ' ' . ($kcUser['family_name'] ?? ''))
                        ?: $username;

            if (!$username) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Gagal mendapatkan informasi user dari SSO',
                ], 422);
            }

            // Find or create local user
            $user = User::where('username', $username)->first();

            if (!$user) {
                $user = User::create([
                    'username' => $username,
                    'email'    => $email,
                    'name'     => $name,
                    'password' => Str::random(32), // random password (auto-hashed by model cast)
                ]);
            } else {
                // Update name/email from SSO if changed
                $user->update([
                    'name'  => $name,
                    'email' => $email,
                ]);
            }

            $token = $user->createToken('petugas-sso-token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Login SSO berhasil',
                'user'    => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
                'token'    => $token,
                'is_admin' => true,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memverifikasi token SSO: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get current authenticated user info.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user'   => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
            ],
        ]);
    }
}
