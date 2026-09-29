<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apotek;
use App\Models\Dokter;
use App\Models\Notifikasi;
use App\Models\Pasien;
use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Helper to resolve model and table by role.
     */
    protected function getModelAndTableByRole(?string $role): array
    {
        return match (strtolower((string) $role)) {
            'dokter' => [Dokter::class, 'dokter', 'id_dokter'],
            'apotek' => [Apotek::class, 'apotek', 'id_apotek'],
            'user' => [User::class, 'users', 'id'],
            default => [Pasien::class, 'pasien', 'id_pasien'],
        };
    }

    /**
     * Find user across multiple roles if role is not specified.
     */
    protected function findUserByEmail(string $email, ?string $role = null): ?array
    {
        if ($role) {
            [$modelClass, , $pk] = $this->getModelAndTableByRole($role);
            $user = $modelClass::where('email', $email)->first();
            return $user ? ['user' => $user, 'role' => strtolower($role), 'pk' => $pk] : null;
        }

        // Try Pasien first
        if ($user = Pasien::where('email', $email)->first()) {
            return ['user' => $user, 'role' => 'pasien', 'pk' => 'id_pasien'];
        }

        // Try Dokter
        if ($user = Dokter::where('email', $email)->first()) {
            return ['user' => $user, 'role' => 'dokter', 'pk' => 'id_dokter'];
        }

        // Try Apotek
        if ($user = Apotek::where('email', $email)->first()) {
            return ['user' => $user, 'role' => 'apotek', 'pk' => 'id_apotek'];
        }

        // Try Default User
        if ($user = User::where('email', $email)->first()) {
            return ['user' => $user, 'role' => 'user', 'pk' => 'id'];
        }

        return null;
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
            'email' => 'required|string|email|max:255|unique:pasien,email',
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
        $data['password'] = Hash::make($data['password']);

        $pasien = Pasien::create($data);
        $token = $pasien->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan untuk pasien baru
        Notifikasi::create([
            'id_user' => $pasien->id_pasien,
            'role' => 'pasien',
            'judul' => 'Selamat Datang di GIAT!',
            'pesan' => 'Halo ' . $pasien->nama . ', akun Anda berhasil didaftarkan. Jaga kesehatan ginjal Anda bersama GIAT.',
            'tipe' => 'umum',
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
            'email' => 'required|string|email|max:255|unique:dokter,email',
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
        $data['password'] = Hash::make($data['password']);

        $dokter = Dokter::create($data);
        $token = $dokter->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan dokter
        Notifikasi::create([
            'id_user' => $dokter->id_dokter,
            'role' => 'dokter',
            'judul' => 'Selamat Datang di GIAT Partner!',
            'pesan' => 'Halo ' . $dokter->nama . ', akun dokter Anda telah aktif. Anda siap melayani konsultasi pasien.',
            'tipe' => 'umum',
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
            'email' => 'required|string|email|max:255|unique:apotek,email',
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
        $data['password'] = Hash::make($data['password']);

        $apotek = Apotek::create($data);
        $token = $apotek->createToken('auth_token')->plainTextToken;

        // Buat notifikasi sambutan apotek
        Notifikasi::create([
            'id_user' => $apotek->id_apotek,
            'role' => 'apotek',
            'judul' => 'Selamat Datang di GIAT Pharmacy Network!',
            'pesan' => 'Halo ' . $apotek->nama . ', akun apotek Anda telah aktif.',
            'tipe' => 'umum',
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
}
