<?php

namespace Tests\Feature;

use App\Models\Pasien;
use App\Services\FirebaseService;
use Mockery;
use Tests\TestCase;

class FirebaseAuthTest extends TestCase
{
    /**
     * Test validation on Firebase Register endpoint.
     */
    public function test_firebase_register_validation(): void
    {
        $response = $this->postJson('/api/auth/firebase/register', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi registrasi Firebase gagal',
            ])
            ->assertJsonValidationErrors(['email', 'password', 'nama']);
    }

    /**
     * Test validation on Firebase Login endpoint.
     */
    public function test_firebase_login_validation(): void
    {
        $response = $this->postJson('/api/auth/firebase/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi login Firebase gagal',
            ])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test validation on Google Sign-In endpoint.
     */
    public function test_google_sign_in_validation(): void
    {
        $response = $this->postJson('/api/auth/firebase/google', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi Google Sign-In gagal',
            ])
            ->assertJsonValidationErrors(['id_token']);
    }

    /**
     * Test validation on Verify Firebase Token endpoint.
     */
    public function test_verify_firebase_token_validation(): void
    {
        $response = $this->postJson('/api/auth/firebase/verify-token', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi token Firebase gagal',
            ])
            ->assertJsonValidationErrors(['id_token']);
    }

    /**
     * Test validation on Firebase Forgot Password endpoint.
     */
    public function test_firebase_forgot_password_validation(): void
    {
        $response = $this->postJson('/api/auth/firebase/forgot-password', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi email gagal',
            ])
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test successful Firebase Register with mocked FirebaseService.
     */
    public function test_firebase_register_success(): void
    {
        $email = 'fb.test.' . time() . '@gmail.com';
        $firebaseUid = 'fb_uid_' . md5($email);

        $mockFirebase = Mockery::mock(FirebaseService::class);
        $mockFirebase->shouldReceive('signUpWithEmailPassword')
            ->once()
            ->with($email, 'password123', 'Pasien Firebase Test')
            ->andReturn([
                'firebase_uid' => $firebaseUid,
                'email' => $email,
                'id_token' => 'mock_fb_id_token_123',
                'refresh_token' => 'mock_fb_refresh_token_123',
                'expires_in' => '3600',
            ]);

        $this->app->instance(FirebaseService::class, $mockFirebase);

        $response = $this->postJson('/api/auth/firebase/register', [
            'email' => $email,
            'password' => 'password123',
            'nama' => 'Pasien Firebase Test',
            'role' => 'pasien',
            'no_hp' => '081234567891',
            'jenis_kelamin' => 'L',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi native Firebase berhasil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id_pasien', 'email', 'firebase_uid', 'auth_provider'],
                    'role',
                    'access_token',
                    'token_type',
                    'firebase' => ['firebase_uid', 'id_token'],
                ],
            ]);

        $this->assertDatabaseHas('user', [
            'email' => $email,
            'firebase_uid' => $firebaseUid,
            'auth_provider' => 'firebase',
        ]);
    }

    /**
     * Test successful Google Sign-In with mocked FirebaseService.
     */
    public function test_google_sign_in_success(): void
    {
        $googleEmail = 'google.user.' . time() . '@gmail.com';
        $googleUid = 'google_uid_' . md5($googleEmail);

        $mockFirebase = Mockery::mock(FirebaseService::class);
        $mockFirebase->shouldReceive('signInWithGoogleCredential')
            ->once()
            ->with('valid_mock_google_id_token')
            ->andReturn([
                'firebase_uid' => $googleUid,
                'email' => $googleEmail,
                'display_name' => 'Budi Google Test',
                'photo_url' => 'https://lh3.googleusercontent.com/a/photo_123',
                'id_token' => 'mock_firebase_google_id_token',
                'refresh_token' => 'mock_refresh',
                'expires_in' => '3600',
                'provider_id' => 'google.com',
                'is_new_user' => true,
            ]);

        $this->app->instance(FirebaseService::class, $mockFirebase);

        $response = $this->postJson('/api/auth/firebase/google', [
            'id_token' => 'valid_mock_google_id_token',
            'role' => 'pasien',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id_pasien', 'email', 'firebase_uid', 'auth_provider'],
                    'role',
                    'access_token',
                    'token_type',
                    'firebase',
                ],
            ]);

        $this->assertDatabaseHas('user', [
            'email' => $googleEmail,
            'firebase_uid' => $googleUid,
            'auth_provider' => 'google',
        ]);
    }

    /**
     * Test successful Firebase Login with mocked FirebaseService.
     */
    public function test_firebase_login_success(): void
    {
        $email = 'fb.login.' . time() . '@gmail.com';
        $firebaseUid = 'fb_uid_' . md5($email);

        // Pre-create user and patient profile
        $user = \App\Models\User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => $email,
            'password' => bcrypt('password123'),
            'role' => 'pasien',
            'firebase_uid' => $firebaseUid,
            'auth_provider' => 'firebase',
        ]);

        $pasien = Pasien::create([
            'id_pasien' => $user->id,
            'nama' => 'Pasien Login Test',
        ]);

        $mockFirebase = Mockery::mock(FirebaseService::class);
        $mockFirebase->shouldReceive('signInWithEmailPassword')
            ->once()
            ->with($email, 'password123')
            ->andReturn([
                'firebase_uid' => $firebaseUid,
                'email' => $email,
                'display_name' => 'Pasien Login Test',
                'id_token' => 'mock_fb_login_token_456',
                'refresh_token' => 'mock_refresh_456',
                'expires_in' => '3600',
            ]);

        $this->app->instance(FirebaseService::class, $mockFirebase);

        $response = $this->postJson('/api/auth/firebase/login', [
            'email' => $email,
            'password' => 'password123',
            'role' => 'pasien',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login Firebase berhasil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'role',
                    'access_token',
                    'token_type',
                    'firebase',
                ],
            ]);
    }
}
