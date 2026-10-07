<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apotek;
use App\Models\Dokter;
use App\Models\Notifikasi;
use App\Models\Pasien;
use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    /**
     * Helper to resolve model and table by role.
     */
    protected function getModelAndTableByRole(?string $role): array
    {
        return match (strtolower((string) $role)) {
            'dokter' => [Dokter::class, 'dokter', 'id_dokter'],
            'apotek' => [Apotek::class, 'apotek', 'id_apotek'],
            'user' => [User::class, 'user', 'id'],
            default => [Pasien::class, 'pasien', 'id_pasien'],
        };
    }

    /**
     * Find user across multiple roles if role is not specified.
     */
    protected function findUserByEmail(string $email, ?string $role = null): ?array
    {
        $query = User::where('email', $email);
        if ($role && in_array(strtolower($role), ['pasien', 'dokter', 'apotek'])) {
            $query->where('role', strtolower($role));
        }

        $userRecord = $query->first();
        if (! $userRecord) {
            return null;
        }

        $roleName = strtolower($userRecord->role);
        $profile = match ($roleName) {
            'dokter' => Dokter::where('id_dokter', $userRecord->id)->first(),
            'apotek' => Apotek::where('id_apotek', $userRecord->id)->first(),
            default => Pasien::where('id_pasien', $userRecord->id)->first(),
        };

        $model = $profile ?? $userRecord;
        $model->password = $userRecord->password;

        return [
            'user' => $model,
            'role' => $roleName,
            'pk' => $model->getKeyName(),
            'user_record' => $userRecord,
        ];
    }

    /**
     * Find user across multiple roles by firebase_uid.
     */
    protected function findUserByFirebaseUid(string $uid, ?string $role = null): ?array
    {
        $query = User::where('firebase_uid', $uid);
        if ($role && in_array(strtolower($role), ['pasien', 'dokter', 'apotek'])) {
            $query->where('role', strtolower($role));
        }

        $userRecord = $query->first();
        if (! $userRecord) {
            return null;
        }

        $roleName = strtolower($userRecord->role);
        $profile = match ($roleName) {
            'dokter' => Dokter::where('id_dokter', $userRecord->id)->first(),
            'apotek' => Apotek::where('id_apotek', $userRecord->id)->first(),
            default => Pasien::where('id_pasien', $userRecord->id)->first(),
        };

        $model = $profile ?? $userRecord;
        $model->password = $userRecord->password;

        return [
            'user' => $model,
            'role' => $roleName,
            'pk' => $model->getKeyName(),
            'user_record' => $userRecord,
        ];
    }

    /**
     * Register a new user (Pasien, Dokter, or Apotek).
     */
    public function register(Request $request): JsonResponse
    {
        $role = strtolower($request->input('role', 'pasien'));

        return match ($role) {
            'dokter' => $this->registerDokter($request),
            'apotek' => $this->registerApotek($request),
            default => $this->registerPasien($request),
        };
    }

    /**
     * Register Pasien.
     */
    public function registerPasien(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|string|in:L,P,Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date',
            'gol_darah' => 'nullable|string|max:5',
            'NIK' => 'nullable|string|max:20',
            'foto_profile' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi registrasi pasien gagal', 422, $validator->errors());
        }

        $data = $validator->validated();
        $password = Hash::make($data['password']);

        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => $data['email'],
            'password' => $password,
            'role' => 'pasien',
            'auth_provider' => 'local',
        ]);

        $pasien = Pasien::create([
            'id_pasien' => $user->id,
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            'nik' => $data['NIK'] ?? $data['nik'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'golongan_darah' => $data['gol_darah'] ?? $data['golongan_darah'] ?? null,
            'foto_profile' => $data['foto_profile'] ?? null,
        ]);

        $token = $pasien->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan untuk pasien baru
        Notifikasi::create([
            'id_user' => $user->id,
            'kategori' => 'sistem',
            'judul' => 'Selamat Datang di GIAT!',
            'pesan' => 'Halo ' . $pasien->nama . ', akun Anda berhasil didaftarkan. Jaga kesehatan ginjal Anda bersama GIAT.',
        ]);

        return $this->successResponse([
            'user' => $pasien,
            'role' => 'pasien',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi pasien berhasil', 201);
    }

    /**
     * Register Dokter.
     */
    public function registerDokter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'no_sip' => 'nullable|string|max:100',
            'no_str' => 'nullable|string|max:100',
            'spesialisasi' => 'required|string|in:Dokter Umum,Dokter Spesialis Penyakit Dalam,Dokter Ginjal',
            'institusi' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|string|in:L,P,Laki-laki,Perempuan',
            'alamat_praktik' => 'nullable|string',
            'foto_profil' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi registrasi dokter gagal', 422, $validator->errors());
        }

        $data = $validator->validated();
        $password = Hash::make($data['password']);

        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => $data['email'],
            'password' => $password,
            'role' => 'dokter',
            'auth_provider' => 'local',
        ]);

        $dokter = Dokter::create([
            'id_dokter' => $user->id,
            'nama' => $data['nama'],
            'spesialisasi' => $data['spesialisasi'],
            'institusi' => $data['institusi'] ?? null,
            'no_str' => $data['no_str'] ?? null,
            'no_sip' => $data['no_sip'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'tarif_konsultasi' => 50000,
            'foto_profile' => $data['foto_profil'] ?? $data['foto_profile'] ?? null,
        ]);

        $token = $dokter->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan dokter
        Notifikasi::create([
            'id_user' => $user->id,
            'kategori' => 'sistem',
            'judul' => 'Selamat Datang di GIAT Partner!',
            'pesan' => 'Halo ' . $dokter->nama . ', akun dokter Anda telah aktif. Anda siap melayani konsultasi pasien.',
        ]);

        return $this->successResponse([
            'user' => $dokter,
            'role' => 'dokter',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi dokter berhasil', 201);
    }

    /**
     * Register Apotek.
     */
    public function registerApotek(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'no_sip' => 'nullable|string|max:100',
            'jam_operasional' => 'nullable|string|max:100',
            'lokasi_apotek' => 'nullable|string',
            'area_layanan' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi registrasi apotek gagal', 422, $validator->errors());
        }

        $data = $validator->validated();
        $password = Hash::make($data['password']);

        $user = User::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => $data['email'],
            'password' => $password,
            'role' => 'apotek',
            'auth_provider' => 'local',
        ]);

        $apotek = Apotek::create([
            'id_apotek' => $user->id,
            'nama_apotek' => $data['nama'],
            'penanggung_jawab' => $data['penanggung_jawab'] ?? $data['nama'],
            'no_sipa_sia' => $data['no_sip'] ?? null,
            'lokasi_lat_long' => $data['lokasi_apotek'] ?? null,
        ]);

        $token = $apotek->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan apotek
        Notifikasi::create([
            'id_user' => $user->id,
            'kategori' => 'sistem',
            'judul' => 'Selamat Datang di GIAT Pharmacy Network!',
            'pesan' => 'Halo ' . $apotek->nama_apotek . ', akun apotek Anda telah aktif.',
        ]);

        return $this->successResponse([
            'user' => $apotek,
            'role' => 'apotek',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi apotek berhasil', 201);
    }

    /**
     * Login for Pasien, Dokter, or Apotek.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi login gagal', 422, $validator->errors());
        }

        $userMeta = $this->findUserByEmail($request->email, $request->role);

        if (! $userMeta || ! Hash::check($request->password, $userMeta['user']->password)) {
            return $this->errorResponse('Email atau password yang Anda masukkan salah.', 401);
        }

        $user = $userMeta['user'];
        $role = $userMeta['role'];

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    /**
     * Request password reset token / OTP.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi forgot password gagal', 422, $validator->errors());
        }

        $userMeta = $this->findUserByEmail($request->email, $request->role);

        if (! $userMeta) {
            return $this->errorResponse('Akun dengan email tersebut tidak ditemukan.', 404);
        }

        $user = $userMeta['user'];

        // Generate 6-digit numeric OTP
        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otp,
                'created_at' => Carbon::now(),
            ]
        );

        // Send email notification if configured
        try {
            if (method_exists($user, 'notify')) {
                $user->notify(new ResetPasswordOtpNotification($otp));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->successResponse([
            'email' => $request->email,
            'otp' => $otp, // Disertakan untuk kemudahan integrasi dan testing mobile Flutter
            'expires_in' => '60 minutes',
        ], 'Kode reset password (OTP) telah dikirimkan ke email Anda.');
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi verifikasi OTP gagal', 422, $validator->errors());
        }

        $otpInput = $request->input('otp') ?? $request->input('token');

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $otpInput)
            ->first();

        if (! $resetRecord) {
            return $this->errorResponse('Kode OTP yang Anda masukkan salah atau tidak valid.', 400);
        }

        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->errorResponse('Kode OTP sudah kadaluarsa (lebih dari 60 menit). Silakan minta kode baru.', 400);
        }

        return $this->successResponse([
            'email' => $request->email,
            'otp' => $otpInput,
            'is_valid' => true,
        ], 'Kode OTP valid. Silakan lanjutkan pembuatan kata sandi baru.');
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi kirim ulang OTP gagal', 422, $validator->errors());
        }

        $userMeta = $this->findUserByEmail($request->email, $request->role);

        if (! $userMeta) {
            return $this->errorResponse('Akun dengan email tersebut tidak ditemukan.', 404);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $otp,
                'created_at' => Carbon::now(),
            ]
        );

        try {
            if (method_exists($userMeta['user'], 'notify')) {
                $userMeta['user']->notify(new ResetPasswordOtpNotification($otp));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->successResponse([
            'email' => $request->email,
            'otp' => $otp,
            'expires_in' => '60 minutes',
        ], 'Kode OTP baru telah berhasil dikirimkan.');
    }

    /**
     * Reset password using OTP/token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'nullable|string',
            'otp' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi reset password gagal', 422, $validator->errors());
        }

        $token = $request->token ?? $request->otp;
        if (! $token) {
            return $this->errorResponse('Parameter token atau otp wajib disertakan', 422);
        }

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $token)
            ->first();

        if (! $resetRecord) {
            return $this->errorResponse('Kode OTP / token reset password tidak valid atau sudah kadaluarsa.', 400);
        }

        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->errorResponse('Kode OTP / token reset password sudah kadaluarsa (lebih dari 60 menit). Silakan minta kode baru.', 400);
        }

        $userMeta = $this->findUserByEmail($request->email, $request->role);

        if (! $userMeta) {
            return $this->errorResponse('Pengguna tidak ditemukan.', 404);
        }

        $user = $userMeta['user'];
        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke all previous tokens for security
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        // Delete used reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->successResponse(null, 'Password berhasil direset. Silakan login kembali dengan password baru Anda.');
    }

    /**
     * Get current authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated. Sesi login tidak valid atau token telah berakhir.', 401);
        }

        $role = match (get_class($user)) {
            Dokter::class => 'dokter',
            Apotek::class => 'apotek',
            User::class => 'user',
            default => 'pasien',
        };

        return $this->successResponse([
            'user' => $user,
            'role' => $role,
        ], 'Informasi sesi akun pengguna saat ini');
    }

    /**
     * Logout and revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Logout berhasil. Token otentikasi telah dicabut.');
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationRead(Request $request, int|string $id): JsonResponse
    {
        $notif = Notifikasi::find($id);

        if (! $notif) {
            return $this->errorResponse('Notifikasi tidak ditemukan', 404);
        }

        $notif->update(['is_read' => true]);

        return $this->successResponse($notif, 'Notifikasi telah ditandai sebagai dibaca');
    }

    // =========================================================================
    // 3. FIREBASE AUTHENTICATION (Native Email/Password, Google Sign-in & Token Sync)
    // =========================================================================

    /**
     * Register user using native Firebase Email & Password.
     */
    public function registerFirebase(Request $request): JsonResponse
    {
        $role = strtolower($request->input('role', 'pasien'));

        $baseRules = [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'nama' => 'required|string|max:255',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
            'no_hp' => 'nullable|string|max:20',
        ];

        $roleRules = match ($role) {
            'dokter' => [
                'no_sip' => 'nullable|string|max:100',
                'no_str' => 'nullable|string|max:100',
                'spesialisasi' => 'required|string',
                'institusi' => 'nullable|string|max:255',
                'alamat_praktik' => 'nullable|string',
                'foto_profil' => 'nullable|string',
            ],
            'apotek' => [
                'no_sip' => 'nullable|string|max:100',
                'jam_operasional' => 'nullable|string|max:100',
                'lokasi_apotek' => 'nullable|string',
                'area_layanan' => 'nullable|string|max:255',
            ],
            default => [
                'alamat' => 'nullable|string',
                'jenis_kelamin' => 'nullable|string|in:L,P,Laki-laki,Perempuan',
                'tanggal_lahir' => 'nullable|date',
                'gol_darah' => 'nullable|string|max:5',
                'NIK' => 'nullable|string|max:20',
                'foto_profile' => 'nullable|string',
            ],
        };

        $validator = Validator::make($request->all(), array_merge($baseRules, $roleRules));

        if ($validator->fails()) {
            return $this->errorResponse('Validasi registrasi Firebase gagal', 422, $validator->errors());
        }

        if (User::where('email', $request->email)->exists()) {
            return $this->errorResponse("Email {$request->email} sudah terdaftar di sistem sebagai {$role}.", 422);
        }

        // 1. Daftarkan akun di Firebase Auth
        try {
            $fbResult = $this->firebaseService->signUpWithEmailPassword(
                $request->email,
                $request->password,
                $request->nama
            );
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        // 2. Simpan profil pengguna di database lokal
        $userId = (string) \Illuminate\Support\Str::uuid();
        $user = User::create([
            'id' => $userId,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'firebase_uid' => $fbResult['firebase_uid'],
            'auth_provider' => 'firebase',
        ]);

        $profile = match ($role) {
            'dokter' => Dokter::create([
                'id_dokter' => $userId,
                'nama' => $request->nama,
                'spesialisasi' => $request->spesialisasi ?? 'Dokter Umum',
                'institusi' => $request->institusi ?? null,
                'no_sip' => $request->no_sip ?? null,
                'no_str' => $request->no_str ?? null,
                'no_hp' => $request->no_hp ?? null,
                'tarif_konsultasi' => 50000,
                'foto_profile' => $request->foto_profil ?? $request->foto_profile ?? null,
            ]),
            'apotek' => Apotek::create([
                'id_apotek' => $userId,
                'nama_apotek' => $request->nama,
                'penanggung_jawab' => $request->penanggung_jawab ?? $request->nama,
                'no_sipa_sia' => $request->no_sip ?? null,
                'no_hp' => $request->no_hp ?? null,
                'lokasi_lat_long' => $request->lokasi_apotek ?? null,
            ]),
            default => Pasien::create([
                'id_pasien' => $userId,
                'nama' => $request->nama,
                'no_hp' => $request->no_hp ?? null,
                'alamat' => $request->alamat ?? null,
                'jenis_kelamin' => $request->jenis_kelamin ?? null,
                'nik' => $request->NIK ?? $request->nik ?? null,
                'tanggal_lahir' => $request->tanggal_lahir ?? null,
                'golongan_darah' => $request->gol_darah ?? $request->golongan_darah ?? null,
                'foto_profile' => $request->foto_profile ?? null,
            ]),
        };

        $token = $profile->createToken('auth_token')->plainTextToken;

        // 3. Notifikasi sambutan
        Notifikasi::create([
            'id_user' => $userId,
            'kategori' => 'sistem',
            'judul' => 'Selamat Datang di GIAT!',
            'pesan' => 'Halo ' . $request->nama . ', akun Anda berhasil didaftarkan via Firebase. Jaga kesehatan ginjal Anda bersama GIAT.',
        ]);

        return $this->successResponse([
            'user' => $profile,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'firebase' => $fbResult,
        ], 'Registrasi native Firebase berhasil', 201);
    }

    /**
     * Login user using native Firebase Email & Password.
     */
    public function loginFirebase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi login Firebase gagal', 422, $validator->errors());
        }

        // 1. Verifikasi kredensial ke Firebase Auth
        try {
            $fbResult = $this->firebaseService->signInWithEmailPassword($request->email, $request->password);
        } catch (\Throwable $e) {
            $localUserMeta = $this->findUserByEmail($request->email, $request->role);
            if ($localUserMeta && Hash::check($request->password, $localUserMeta['user_record']->password)) {
                try {
                    $fbResult = $this->firebaseService->signUpWithEmailPassword(
                        $request->email,
                        $request->password,
                        $localUserMeta['user']->nama ?? null
                    );
                } catch (\Throwable $signUpErr) {
                    return $this->errorResponse($e->getMessage(), 401);
                }
            } else {
                return $this->errorResponse($e->getMessage(), 401);
            }
        }

        // 2. Sinkronkan dengan user di database lokal
        $userMeta = $this->findUserByFirebaseUid($fbResult['firebase_uid'], $request->role)
            ?? $this->findUserByEmail($request->email, $request->role);

        $role = $userMeta ? $userMeta['role'] : ($request->role ? strtolower($request->role) : 'pasien');

        if (! $userMeta) {
            $userId = (string) \Illuminate\Support\Str::uuid();
            $userRecord = User::create([
                'id' => $userId,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $role,
                'firebase_uid' => $fbResult['firebase_uid'],
                'auth_provider' => 'firebase',
            ]);

            $user = match ($role) {
                'dokter' => Dokter::create([
                    'id_dokter' => $userId,
                    'nama' => $fbResult['display_name'] ?? explode('@', $request->email)[0],
                    'spesialisasi' => 'Dokter Umum',
                ]),
                'apotek' => Apotek::create([
                    'id_apotek' => $userId,
                    'nama_apotek' => $fbResult['display_name'] ?? explode('@', $request->email)[0],
                ]),
                default => Pasien::create([
                    'id_pasien' => $userId,
                    'nama' => $fbResult['display_name'] ?? explode('@', $request->email)[0],
                ]),
            };
        } else {
            $user = $userMeta['user'];
            $role = $userMeta['role'];
            $userMeta['user_record']->update([
                'firebase_uid' => $fbResult['firebase_uid'],
                'password' => Hash::make($request->password),
                'auth_provider' => 'firebase',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'firebase' => $fbResult,
        ], 'Login Firebase berhasil');
    }

    /**
     * Sign-in / Sign-up using Google (via Firebase / Google ID Token).
     */
    public function googleSignIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
            'nama' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi Google Sign-In gagal', 422, $validator->errors());
        }

        $idToken = $request->input('id_token');

        // 1. Verifikasi kredensial token ke Firebase / Google OAuth2
        try {
            $googleData = $this->firebaseService->signInWithGoogleCredential($idToken);
        } catch (\Throwable $e1) {
            try {
                $googleData = $this->firebaseService->verifyIdToken($idToken);
            } catch (\Throwable $e2) {
                try {
                    $googleData = $this->firebaseService->verifyGoogleIdToken($idToken);
                } catch (\Throwable $e3) {
                    return $this->errorResponse('Verifikasi Google Sign-In gagal: ' . $e3->getMessage(), 401);
                }
            }
        }

        $email = $googleData['email'] ?? null;
        $firebaseUid = $googleData['firebase_uid'] ?? null;

        if (empty($email)) {
            return $this->errorResponse('Akun Google tidak memiliki alamat email yang valid.', 400);
        }

        // 2. Cari pengguna yang sudah ada berdasarkan firebase_uid atau email
        $userMeta = null;
        if (!empty($firebaseUid)) {
            $userMeta = $this->findUserByFirebaseUid($firebaseUid, $request->role);
        }
        if (!$userMeta) {
            $userMeta = $this->findUserByEmail($email, $request->role);
        }

        $role = $request->role ? strtolower($request->role) : ($userMeta['role'] ?? 'pasien');
        $isNewUser = false;

        if (! $userMeta) {
            $userId = (string) \Illuminate\Support\Str::uuid();
            $nama = $request->input('nama') ?: ($googleData['display_name'] ?? explode('@', $email)[0]);

            $userRecord = User::create([
                'id' => $userId,
                'email' => $email,
                'role' => $role,
                'firebase_uid' => $firebaseUid,
                'auth_provider' => 'google',
            ]);

            $user = match ($role) {
                'dokter' => Dokter::create([
                    'id_dokter' => $userId,
                    'nama' => $nama,
                    'spesialisasi' => 'Dokter Umum',
                    'foto_profile' => $googleData['photo_url'] ?? null,
                ]),
                'apotek' => Apotek::create([
                    'id_apotek' => $userId,
                    'nama_apotek' => $nama,
                ]),
                default => Pasien::create([
                    'id_pasien' => $userId,
                    'nama' => $nama,
                    'no_hp' => $request->input('no_hp'),
                    'foto_profile' => $googleData['photo_url'] ?? null,
                ]),
            };

            $isNewUser = true;

            Notifikasi::create([
                'id_user' => $userId,
                'kategori' => 'sistem',
                'judul' => 'Selamat Datang di GIAT!',
                'pesan' => 'Halo ' . $nama . ', akun Anda berhasil terhubung menggunakan Google. Jaga kesehatan ginjal Anda bersama GIAT.',
            ]);
        } else {
            $user = $userMeta['user'];
            $role = $userMeta['role'];

            $userMeta['user_record']->update([
                'firebase_uid' => $firebaseUid ?? $userMeta['user_record']->firebase_uid,
                'auth_provider' => 'google',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $isNewUser,
            'firebase' => $googleData,
        ], $isNewUser ? 'Pendaftaran via Google berhasil' : 'Login via Google berhasil');
    }

    /**
     * Verify any Firebase ID Token directly and sync with local session.
     * Ideal for Flutter / mobile clients that have already signed in with Firebase SDK.
     */
    public function verifyFirebaseToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'role' => 'nullable|string|in:pasien,dokter,apotek,user',
            'nama' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi token Firebase gagal', 422, $validator->errors());
        }

        try {
            $fbData = $this->firebaseService->verifyIdToken($request->id_token);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }

        $email = $fbData['email'] ?? null;
        $firebaseUid = $fbData['firebase_uid'] ?? null;

        if (empty($email)) {
            return $this->errorResponse('Token Firebase tidak memuat email pengguna.', 400);
        }

        $userMeta = null;
        if (!empty($firebaseUid)) {
            $userMeta = $this->findUserByFirebaseUid($firebaseUid, $request->role);
        }
        if (!$userMeta) {
            $userMeta = $this->findUserByEmail($email, $request->role);
        }

        $role = $request->role ? strtolower($request->role) : ($userMeta['role'] ?? 'pasien');

        if (! $userMeta) {
            [$modelClass] = $this->getModelAndTableByRole($role);
            $nama = $request->nama ?: ($fbData['display_name'] ?? explode('@', $email)[0]);

            $createData = [
                'nama' => $nama,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                'auth_provider' => $fbData['provider_id'] ?? 'firebase',
                'no_hp' => $request->no_hp,
            ];

            if ($role === 'dokter') {
                $createData['foto_profil'] = $fbData['photo_url'] ?? null;
                $createData['spesialisasi'] = 'Dokter Umum';
            } else {
                $createData['foto_profile'] = $fbData['photo_url'] ?? null;
            }

            $user = $modelClass::create($createData);
        } else {
            $user = $userMeta['user'];
            $role = $userMeta['role'];
            if (!empty($firebaseUid)) {
                $user->firebase_uid = $firebaseUid;
            }
            $user->auth_provider = $fbData['provider_id'] ?? 'firebase';
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'role' => $role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'firebase' => $fbData,
        ], 'Verifikasi token Firebase berhasil');
    }

    /**
     * Send password reset email via Firebase Auth.
     */
    public function firebaseForgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi email gagal', 422, $validator->errors());
        }

        try {
            $this->firebaseService->sendPasswordResetEmail($request->email);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        return $this->successResponse([
            'email' => $request->email,
        ], 'Email pemulihan kata sandi telah dikirimkan oleh Firebase. Silakan periksa kotak masuk atau spam email Anda.');
    }
}
