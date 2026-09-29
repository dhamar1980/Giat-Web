<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $apiKey;
    protected string $projectId;
    protected string $identityToolkitUrl = 'https://identitytoolkit.googleapis.com/v1';

    public function __construct()
    {
        $this->apiKey = (string) config('firebase.api_key', env('FIREBASE_API_KEY', ''));
        $this->projectId = (string) config('firebase.project_id', env('FIREBASE_PROJECT_ID', ''));
    }

    /**
     * Check if Firebase configuration is set.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get configured Project ID.
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * Register a new user using native Firebase Email & Password.
     *
     * @param string $email
     * @param string $password
     * @param string|null $displayName
     * @return array
     * @throws Exception
     */
    public function signUpWithEmailPassword(string $email, string $password, ?string $displayName = null): array
    {
        $this->ensureConfigured();

        $response = Http::post("{$this->identityToolkitUrl}/accounts:signUp?key={$this->apiKey}", [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);

        if ($response->failed()) {
            $this->handleFirebaseError($response->json());
        }

        $data = $response->json();

        // Jika display name disertakan, update profil akun di Firebase
        if ($displayName && !empty($data['idToken'])) {
            try {
                $this->updateProfile($data['idToken'], $displayName);
                $data['displayName'] = $displayName;
            } catch (\Throwable $e) {
                Log::warning('Firebase update display name failed: ' . $e->getMessage());
            }
        }

        return [
            'firebase_uid' => $data['localId'] ?? null,
            'email' => $data['email'] ?? $email,
            'id_token' => $data['idToken'] ?? null,
            'refresh_token' => $data['refreshToken'] ?? null,
            'expires_in' => $data['expiresIn'] ?? null,
        ];
    }

    /**
     * Sign in user using native Firebase Email & Password.
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws Exception
     */
    public function signInWithEmailPassword(string $email, string $password): array
    {
        $this->ensureConfigured();

        $response = Http::post("{$this->identityToolkitUrl}/accounts:signInWithPassword?key={$this->apiKey}", [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);

        if ($response->failed()) {
            $this->handleFirebaseError($response->json());
        }

        $data = $response->json();

        return [
            'firebase_uid' => $data['localId'] ?? null,
            'email' => $data['email'] ?? $email,
            'display_name' => $data['displayName'] ?? null,
            'id_token' => $data['idToken'] ?? null,
            'refresh_token' => $data['refreshToken'] ?? null,
            'expires_in' => $data['expiresIn'] ?? null,
        ];
    }

    /**
     * Verify Firebase ID Token and retrieve user details.
     *
     * @param string $idToken
     * @return array
     * @throws Exception
     */
    public function verifyIdToken(string $idToken): array
    {
        $this->ensureConfigured();

        // 1. Validasi via Firebase Identity Toolkit Lookup
        $response = Http::post("{$this->identityToolkitUrl}/accounts:lookup?key={$this->apiKey}", [
            'idToken' => $idToken,
        ]);

        if ($response->failed()) {
            $this->handleFirebaseError($response->json());
        }

        $data = $response->json();
        $user = $data['users'][0] ?? null;

        if (!$user) {
            throw new Exception('Pengguna Firebase tidak ditemukan untuk token ini.');
        }

        // Cari provider info (apakah google.com, password, dll)
        $providers = $user['providerUserInfo'] ?? [];
        $providerId = 'firebase';
        $photoUrl = $user['photoUrl'] ?? null;

        foreach ($providers as $prov) {
            if (!empty($prov['providerId'])) {
                $providerId = $prov['providerId'];
            }
            if (empty($photoUrl) && !empty($prov['photoUrl'])) {
                $photoUrl = $prov['photoUrl'];
            }
        }

        return [
            'firebase_uid' => $user['localId'] ?? null,
            'email' => $user['email'] ?? null,
            'email_verified' => (bool) ($user['emailVerified'] ?? false),
            'display_name' => $user['displayName'] ?? null,
            'photo_url' => $photoUrl,
            'provider_id' => $providerId,
            'raw_user' => $user,
        ];
    }

    /**
     * Sign in or link using Google Credential / ID Token.
     * Firebase signInWithIdp creates/links the user in Firebase Auth and returns Firebase session.
     *
     * @param string $googleIdToken
     * @return array
     * @throws Exception
     */
    public function signInWithGoogleCredential(string $googleIdToken): array
    {
        $this->ensureConfigured();

        $postBody = http_build_query([
            'id_token' => $googleIdToken,
            'providerId' => 'google.com',
        ]);

        $response = Http::post("{$this->identityToolkitUrl}/accounts:signInWithIdp?key={$this->apiKey}", [
            'postBody' => $postBody,
            'requestUri' => 'http://localhost',
            'returnSecureToken' => true,
            'returnIdpCredential' => true,
        ]);

        if ($response->failed()) {
            // Jika signInWithIdp gagal, coba verifikasi tokeninfo Google langsung
            return $this->verifyGoogleIdToken($googleIdToken);
        }

        $data = $response->json();

        return [
            'firebase_uid' => $data['localId'] ?? null,
            'email' => $data['email'] ?? null,
            'display_name' => $data['displayName'] ?? ($data['fullName'] ?? null),
            'photo_url' => $data['photoUrl'] ?? null,
            'id_token' => $data['idToken'] ?? null,
            'refresh_token' => $data['refreshToken'] ?? null,
            'expires_in' => $data['expiresIn'] ?? null,
            'provider_id' => 'google.com',
            'is_new_user' => (bool) ($data['isNewUser'] ?? false),
        ];
    }

    /**
     * Verify Google OAuth ID Token directly via Google OAuth2 API.
     * Useful when mobile client uses native Google Sign-In SDK and sends raw Google id_token.
     *
     * @param string $idToken
     * @return array
     * @throws Exception
     */
    public function verifyGoogleIdToken(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            $err = $response->json()['error_description'] ?? 'Token Google tidak valid atau telah kedaluwarsa.';
            throw new Exception($err);
        }

        $data = $response->json();

        return [
            'firebase_uid' => $data['sub'] ?? null,
            'email' => $data['email'] ?? null,
            'email_verified' => filter_var($data['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'display_name' => $data['name'] ?? null,
            'photo_url' => $data['picture'] ?? null,
            'provider_id' => 'google.com',
            'id_token' => $idToken,
        ];
    }

    /**
     * Send Password Reset Email via Firebase.
     *
     * @param string $email
     * @return bool
     * @throws Exception
     */
    public function sendPasswordResetEmail(string $email): bool
    {
        $this->ensureConfigured();

        $response = Http::post("{$this->identityToolkitUrl}/accounts:sendOobCode?key={$this->apiKey}", [
            'requestType' => 'PASSWORD_RESET',
            'email' => $email,
        ]);

        if ($response->failed()) {
            $this->handleFirebaseError($response->json());
        }

        return true;
    }

    /**
     * Update user profile on Firebase.
     *
     * @param string $idToken
     * @param string|null $displayName
     * @param string|null $photoUrl
     * @return array
     * @throws Exception
     */
    public function updateProfile(string $idToken, ?string $displayName = null, ?string $photoUrl = null): array
    {
        $this->ensureConfigured();

        $payload = [
            'idToken' => $idToken,
            'returnSecureToken' => true,
        ];

        if ($displayName !== null) {
            $payload['displayName'] = $displayName;
        }

        if ($photoUrl !== null) {
            $payload['photoUrl'] = $photoUrl;
        }

        $response = Http::post("{$this->identityToolkitUrl}/accounts:update?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            $this->handleFirebaseError($response->json());
        }

        return $response->json();
    }

    /**
     * Decode JWT Payload without verification (useful for inspecting claims).
     *
     * @param string $jwt
     * @return array|null
     */
    public function decodeJwtPayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if (!$payload) {
            return null;
        }

        return json_decode($payload, true);
    }

    /**
     * Ensure Firebase API key is configured.
     *
     * @throws Exception
     */
    protected function ensureConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new Exception('Firebase Web API Key belum dikonfigurasi. Harap isi FIREBASE_API_KEY di file .env.');
        }
    }

    /**
     * Handle Firebase error and throw user-friendly exception.
     *
     * @param array|null $errorData
     * @throws Exception
     */
    protected function handleFirebaseError(?array $errorData): void
    {
        $rawMessage = $errorData['error']['message'] ?? 'UNKNOWN_ERROR';
        $message = $this->formatErrorMessage($rawMessage);

        throw new Exception($message);
    }

    /**
     * Translate Firebase error messages to user-friendly Indonesian.
     */
    public function formatErrorMessage(string $firebaseError): string
    {
        // Pisahkan jika ada suffix error (misal: "WEAK_PASSWORD : Password should be at least 6 characters")
        $code = trim(explode(':', $firebaseError)[0]);

        return match ($code) {
            'EMAIL_EXISTS' => 'Email ini sudah terdaftar di sistem Firebase.',
            'OPERATION_NOT_ALLOWED' => 'Metode masuk ini belum diaktifkan di Firebase Console (Authentication > Sign-in method).',
            'TOO_MANY_ATTEMPTS_TRY_LATER' => 'Terlalu banyak percobaan yang gagal. Silakan coba lagi beberapa saat lagi.',
            'EMAIL_NOT_FOUND' => 'Akun dengan email tersebut tidak ditemukan di Firebase.',
            'INVALID_PASSWORD', 'INVALID_LOGIN_CREDENTIALS' => 'Email atau kata sandi Firebase yang Anda masukkan salah.',
            'USER_DISABLED' => 'Akun Firebase ini telah dinonaktifkan oleh administrator.',
            'WEAK_PASSWORD' => 'Kata sandi terlalu lemah. Gunakan minimal 6 karakter.',
            'INVALID_ID_TOKEN' => 'Token otentikasi Firebase tidak valid atau sudah kedaluwarsa.',
            'TOKEN_EXPIRED' => 'Sesi token Firebase telah berakhir. Silakan login kembali.',
            'USER_NOT_FOUND' => 'Data pengguna Firebase tidak ditemukan.',
            'FEDERATED_USER_ID_ALREADY_LINKED' => 'Akun Google ini sudah terhubung dengan pengguna lain.',
            default => 'Otentikasi Firebase gagal: ' . $firebaseError,
        };
    }
}
