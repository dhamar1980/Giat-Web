<?php

use App\Http\Controllers\Api\ApotekController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DokterController;
use App\Http\Controllers\Api\KonsultasiController;
use App\Http\Controllers\Api\PasienController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - GIAT (Ginjal Sehat)
|--------------------------------------------------------------------------
| Semua endpoint di aplikasi ini terproteksi oleh Laravel Sanctum Token
| KECUALI: Login, Register, dan Lupa Password / OTP.
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. PUBLIC ROUTES (Login, Register & Forgot Password / OTP)
// =========================================================================
Route::prefix('auth')->group(function () {
    // Register
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/pasien', [AuthController::class, 'registerPasien']);
    Route::post('/register/dokter', [AuthController::class, 'registerDokter']);
    Route::post('/register/apotek', [AuthController::class, 'registerApotek']);

    // Login
    Route::post('/login', [AuthController::class, 'login']);

    // Lupa Password & OTP Flow
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Shortcut level atas (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// =========================================================================
// 2. SANCTUM PROTECTED ROUTES (auth:sanctum)
// Semua endpoint di bawah ini WAJIB menyertakan Header:
// Authorization: Bearer <token>
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- A. Auth Session & Notifikasi Global ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::patch('/notifikasi/{id}/read', [AuthController::class, 'markNotificationRead']);

    // --- B. PASIEN ROUTES ---
    Route::prefix('pasien')->group(function () {
        // 1. Home & Edukasi Kesehatan
        Route::get('/home', [PasienController::class, 'getHome']);
        Route::get('/edukasi', [PasienController::class, 'getEdukasiList']);
        Route::get('/edukasi/{id}', [PasienController::class, 'getEdukasiDetail']);

        // 2. Reminder Pengingat Minum Obat
        Route::get('/reminder', [PasienController::class, 'getReminders']);
        Route::post('/reminder', [PasienController::class, 'createReminder']);
        Route::put('/reminder/{id}', [PasienController::class, 'updateReminder']);
        Route::patch('/reminder/{id}/toggle', [PasienController::class, 'toggleReminder']);
        Route::delete('/reminder/{id}', [PasienController::class, 'deleteReminder']);

        // 3. Profile, Kontak, Password & Foto Profil
        Route::get('/profile', [PasienController::class, 'getProfile']);
        Route::put('/profile', [PasienController::class, 'updateProfile']);
        Route::put('/profile/nohp', [PasienController::class, 'updateNoHp']);
        Route::put('/profile/email', [PasienController::class, 'updateEmail']);
        Route::put('/profile/password', [PasienController::class, 'updatePassword']);
        Route::post('/profile/foto', [PasienController::class, 'updateFotoProfile']);

        // 4. PANTAU (Catatan Kondisi Harian Ginjal & Berat Badan/IMT)
        Route::get('/pantau', [PasienController::class, 'getPantauHistory']);
        Route::post('/pantau', [PasienController::class, 'storePantau']);
        Route::get('/pantau/summary', [PasienController::class, 'getPantauSummary']);

        // 5. PRAGI (Skrining Risiko CKD & Chatbot AI PRAGI)
        Route::get('/pragi/pertanyaan', [PasienController::class, 'getPragiQuestions']);
        Route::post('/pragi/skrining', [PasienController::class, 'submitPragi']);
        Route::get('/pragi/riwayat', [PasienController::class, 'getPragiHistory']);
        Route::post('/pragi/chat', [PasienController::class, 'chatPragi']);

        // 6. OBAT (Katalog Obat Bebas, Resep Dokter, Beli Obat, Riwayat & Lacak)
        Route::get('/obat/katalog', [PasienController::class, 'getObatList']);
        Route::get('/obat/katalog/{id}', [PasienController::class, 'getObatDetail']);
        Route::post('/obat/beli-umum', [PasienController::class, 'beliObatUmum']);
        Route::get('/obat/resep-saya', [PasienController::class, 'getResepPasien']);
        Route::post('/obat/beli-resep', [PasienController::class, 'beliObatResep']);
        Route::get('/obat/riwayat-pembelian', [PasienController::class, 'getRiwayatPembelian']);
        Route::get('/obat/pesanan/{id}', [PasienController::class, 'getDetailPesanan']);
        Route::get('/obat/lacak/{id}', [PasienController::class, 'lacakPesanan']);

        // Notifikasi & Riwayat Konsultasi Pasien
        Route::get('/notifikasi', [PasienController::class, 'getNotifikasi']);
        Route::get('/konsultasi/riwayat', [KonsultasiController::class, 'getPasienKonsultasi']);
    });

    // --- C. KONSULTASI ROUTES (Pasien & Dokter) ---
    Route::prefix('konsultasi')->group(function () {
        // Booking & Informasi Dokter
        Route::get('/kategori-dokter', [KonsultasiController::class, 'getKategoriDokter']);
        Route::get('/dokter', [KonsultasiController::class, 'getDokterList']);
        Route::get('/dokter/{id}', [KonsultasiController::class, 'getDokterDetail']);
        Route::post('/booking', [KonsultasiController::class, 'bookingDokter']);
        Route::get('/{id}', [KonsultasiController::class, 'getDetailKonsultasi']);
        Route::post('/{id}/bayar', [KonsultasiController::class, 'bayarKonsultasi']);
        Route::get('/{id}/status-bayar', [KonsultasiController::class, 'cekStatusPembayaran']);

        // Chat Konsultasi Realtime
        Route::get('/{id}/pesan', [KonsultasiController::class, 'getMessages']);
        Route::post('/{id}/pesan', [KonsultasiController::class, 'sendMessage']);

        // Video Call Dokter - Pasien
        Route::get('/{id}/video-call', [KonsultasiController::class, 'getVideoCall']);

        // Selesaikan Sesi Konsultasi
        Route::post('/{id}/selesai', [KonsultasiController::class, 'selesaikanKonsultasi']);

        // Riwayat per Pengguna
        Route::get('/riwayat/pasien', [KonsultasiController::class, 'getPasienKonsultasi']);
        Route::get('/riwayat/dokter', [KonsultasiController::class, 'getDokterKonsultasi']);
    });

    // --- D. DOKTER ROUTES ---
    Route::prefix('dokter')->group(function () {
        // 1. Dashboard & Notifikasi
        Route::get('/dashboard', [DokterController::class, 'getDashboard']);
        Route::get('/notifikasi', [DokterController::class, 'getNotifikasi']);

        // 2. Jadwal Konsultasi
        Route::get('/jadwal', [DokterController::class, 'getJadwal']);

        // 3. Pasien & Detail Pasien (Pantau harian + PRAGI screening)
        Route::get('/pasien', [DokterController::class, 'getPasienList']);
        Route::get('/pasien/{id}/detail', [DokterController::class, 'getPasienDetail']);

        // 4. Resep Obat Elektronik
        Route::post('/resep', [DokterController::class, 'createResep']);
        Route::get('/resep', [DokterController::class, 'getRiwayatResep']);
        Route::get('/obat-rekomendasi', [DokterController::class, 'getDaftarObatResep']);

        // 5. Profile Dokter & Pengaturan
        Route::get('/profile', [DokterController::class, 'getProfile']);
        Route::put('/profile', [DokterController::class, 'updateProfile']);
        Route::post('/profile/foto', [DokterController::class, 'updateFotoProfil']);
        Route::put('/profile/password', [DokterController::class, 'updatePassword']);
        Route::put('/profile/notifikasi', [DokterController::class, 'updateNotificationSettings']);

        // 6. Manajemen Perangkat Login (Sanctum Tokens)
        Route::get('/profile/perangkat', [DokterController::class, 'getLoginDevices']);
        Route::delete('/profile/perangkat/{id_token}', [DokterController::class, 'revokeDevice']);
    });

    // --- E. APOTEK ROUTES ---
    Route::prefix('apotek')->group(function () {
        // 1. Dashboard & Notifikasi
        Route::get('/dashboard', [ApotekController::class, 'getDashboard']);
        Route::get('/notifikasi', [ApotekController::class, 'getNotifikasi']);

        // 2. Reminder Pasien (Melihat jadwal pengingat pasien)
        Route::get('/reminder', [ApotekController::class, 'getReminders']);

        // 3. Pesanan Obat Pasien (Terima, Proses, Selesaikan, Detail)
        Route::get('/pesanan', [ApotekController::class, 'getPesananList']);
        Route::get('/pesanan/{id}', [ApotekController::class, 'getPesananDetail']);
        Route::post('/pesanan/{id}/terima', [ApotekController::class, 'terimaPesanan']);
        Route::post('/pesanan/{id}/proses', [ApotekController::class, 'prosesPesanan']);
        Route::post('/pesanan/{id}/selesai', [ApotekController::class, 'selesaikanPesanan']);
        Route::post('/pesanan/{id}/tolak', [ApotekController::class, 'tolakPesanan']);

        // 4. Resep Dokter (Pesanan obat resep & validasi resep apoteker)
        Route::get('/resep', [ApotekController::class, 'getPesananResep']);
        Route::post('/resep/{id}/validasi', [ApotekController::class, 'validasiDanTerimaResep']);

        // 5. Obat & Stok Obat (Daftar, Stok, Tambah Obat, Update Stok)
        Route::get('/obat', [ApotekController::class, 'getDaftarObat']);
        Route::get('/obat/stock', [ApotekController::class, 'getStockObat']);
        Route::post('/obat', [ApotekController::class, 'tambahObat']);
        Route::post('/obat/update-stock', [ApotekController::class, 'updateStock']);

        // 6. Profile, Operasional & Area Layanan
        Route::get('/profile', [ApotekController::class, 'getProfile']);
        Route::put('/profile', [ApotekController::class, 'updateProfile']);
        Route::put('/profile/password', [ApotekController::class, 'updatePassword']);
        Route::get('/jam-operasional', [ApotekController::class, 'getJamOperasional']);
        Route::put('/jam-operasional', [ApotekController::class, 'updateJamOperasional']);
        Route::get('/area-layanan', [ApotekController::class, 'getAreaLayanan']);
        Route::put('/area-layanan', [ApotekController::class, 'updateAreaLayanan']);
        Route::patch('/status-layanan', [ApotekController::class, 'toggleStatusLayanan']);
        Route::get('/aktivitas', [ApotekController::class, 'getRiwayatAktivitas']);
    });
});
