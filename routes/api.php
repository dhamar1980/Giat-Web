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
| Seluruh endpoint backend GIAT untuk aplikasi web & mobile Flutter.
| Dilengkapi dengan otentikasi Laravel Sanctum, Firebase Auth sync,
| telemedicine chat/video call, pemantauan ginjal, PRAGI AI & apotek.
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. PUBLIC ROUTES (Login, Register, Lupa Password / OTP, Edukasi & Fallback)
// =========================================================================
Route::prefix('auth')->group(function () {
    // Register
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/pasien', [AuthController::class, 'registerPasien']);
    Route::post('/register/dokter', [AuthController::class, 'registerDokter']);
    Route::post('/register/apotek', [AuthController::class, 'registerApotek']);

    // Login (Named 'login' to prevent Route [login] not defined in Sanctum)
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Logout fallback
    Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout']);

    // Lupa Password & OTP Flow
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Firebase Authentication (Native Email/Password, Google Sign-In & Token Sync)
    Route::prefix('firebase')->group(function () {
        Route::post('/register', [AuthController::class, 'registerFirebase']);
        Route::post('/login', [AuthController::class, 'loginFirebase']);
        Route::post('/google', [AuthController::class, 'googleSignIn']);
        Route::post('/verify-token', [AuthController::class, 'verifyFirebaseToken']);
        Route::post('/forgot-password', [AuthController::class, 'firebaseForgotPassword']);
    });

    // Shortcut Google Sign-In langsung di /auth/google
    Route::post('/google', [AuthController::class, 'googleSignIn']);
});

// Shortcut level atas (Public Routes)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout']);
Route::match(['GET', 'POST'], '/pasien/logout', [AuthController::class, 'logout']);
Route::match(['GET', 'POST'], '/dokter/logout', [AuthController::class, 'logout']);
Route::match(['GET', 'POST'], '/apotek/logout', [AuthController::class, 'logout']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/google-login', [AuthController::class, 'googleSignIn']);

// Public Health Education (Bisa diakses tanpa login / Onboarding)
Route::get('/edukasi', [PasienController::class, 'getEdukasiList']);
Route::get('/edukasi/kategori', [PasienController::class, 'getEdukasiKategori']);
Route::get('/edukasi/{id}', [PasienController::class, 'getEdukasiDetail']);

// Public PRAGI Skrining Questions & ML Transmission Utilities (AI PRAGI)
Route::get('/pragi/pertanyaan', [PasienController::class, 'getPragiQuestions']);
Route::get('/pragi/ai-pertanyaan', [PasienController::class, 'getPragiQuestions']);
Route::get('/pragi/ai/pertanyaan', [PasienController::class, 'getPragiQuestions']);
Route::get('/pragi/ml-status', [PasienController::class, 'checkPragiMlStatus']);
Route::post('/pragi/encode', [PasienController::class, 'encodePragiData']);

// Public Payment Gateway Callback / Webhook Simulator
Route::post('/konsultasi/payment-callback', [KonsultasiController::class, 'paymentCallback']);

// Rating & Ulasan Dokter (Mendukung auth Sanctum opsional / public)
Route::post('/konsultasi/dokter/{id}/rating', [KonsultasiController::class, 'beriRatingDokter']);
Route::post('/konsultasi/dokter/rating', [KonsultasiController::class, 'beriRatingDokter']);
Route::post('/dokter/{id}/rating', [KonsultasiController::class, 'beriRatingDokter']);
Route::get('/konsultasi/dokter/{id}/rating', [KonsultasiController::class, 'getDokterUlasan']);
Route::get('/konsultasi/dokter/{id}/ulasan', [KonsultasiController::class, 'getDokterUlasan']);
Route::get('/dokter/{id}/rating', [KonsultasiController::class, 'getDokterUlasan']);
Route::get('/dokter/{id}/ulasan', [KonsultasiController::class, 'getDokterUlasan']);

// =========================================================================
// 2. SANCTUM PROTECTED ROUTES (auth:sanctum)
// Semua endpoint di bawah ini WAJIB menyertakan Header:
// Authorization: Bearer <token>
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // --- A. Auth Session & Akun Pengguna ---
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::delete('/auth/delete-account', [AuthController::class, 'deleteAccount']);

    // Notifikasi Global (Universal untuk semua role pengguna)
    Route::get('/notifikasi', [AuthController::class, 'getNotifikasi']);
    Route::get('/notifikasi/unread-count', [AuthController::class, 'getUnreadNotificationCount']);
    Route::patch('/notifikasi/{id}/read', [AuthController::class, 'markNotificationRead']);
    Route::match(['POST', 'PATCH'], '/notifikasi/read-all', [AuthController::class, 'markAllNotificationsRead']);
    Route::delete('/notifikasi/{id}', [AuthController::class, 'deleteNotification']);
    Route::delete('/notifikasi', [AuthController::class, 'clearReadNotifications']);

    // Edukasi Artikel Management (CRUD oleh Dokter / Admin)
    Route::post('/edukasi', [PasienController::class, 'createEdukasi']);
    Route::put('/edukasi/{id}', [PasienController::class, 'updateEdukasi']);
    Route::delete('/edukasi/{id}', [PasienController::class, 'deleteEdukasi']);

    // --- B. PASIEN ROUTES ---
    Route::prefix('pasien')->group(function () {
        // 1. Home & Edukasi Kesehatan
        Route::get('/home', [PasienController::class, 'getHome']);
        Route::get('/edukasi', [PasienController::class, 'getEdukasiList']);
        Route::get('/edukasi/kategori', [PasienController::class, 'getEdukasiKategori']);
        Route::get('/edukasi/{id}', [PasienController::class, 'getEdukasiDetail']);

        // 2. Reminder Pengingat Minum Obat
        Route::get('/reminder', [PasienController::class, 'getReminders']);
        Route::get('/reminder/hari-ini', [PasienController::class, 'getTodayReminders']);
        Route::post('/reminder', [PasienController::class, 'createReminder']);
        Route::get('/reminder/{id}', [PasienController::class, 'getReminderDetail']);
        Route::put('/reminder/{id}', [PasienController::class, 'updateReminder']);
        Route::patch('/reminder/{id}/toggle', [PasienController::class, 'toggleReminder']);
        Route::delete('/reminder/{id}', [PasienController::class, 'deleteReminder']);

        // 3. Profile, Kontak, Password, Foto Profil & Hapus Akun
        Route::get('/profile', [PasienController::class, 'getProfile']);
        Route::put('/profile', [PasienController::class, 'updateProfile']);
        Route::put('/profile/nohp', [PasienController::class, 'updateNoHp']);
        Route::put('/profile/email', [PasienController::class, 'updateEmail']);
        Route::put('/profile/password', [PasienController::class, 'updatePassword']);
        Route::post('/profile/foto', [PasienController::class, 'updateFotoProfile']);
        Route::delete('/profile', [PasienController::class, 'deleteProfile']);

        // 4. PANTAU (Catatan Kondisi Harian Ginjal & Berat Badan/IMT)
        Route::get('/pantau', [PasienController::class, 'getPantauHistory']);
        Route::post('/pantau', [PasienController::class, 'storePantau']);
        Route::get('/pantau/summary', [PasienController::class, 'getPantauSummary']);
        Route::get('/pantau/chart', [PasienController::class, 'getPantauChart']);
        Route::get('/pantau/{id}', [PasienController::class, 'getPantauDetail']);
        Route::put('/pantau/{id}', [PasienController::class, 'updatePantau']);
        Route::delete('/pantau/{id}', [PasienController::class, 'deletePantau']);

        // 5. PRAGI (Skrining Risiko CKD, Transmisi Prediksi Python ML & Chatbot AI)
        Route::get('/pragi/pertanyaan', [PasienController::class, 'getPragiQuestions']);
        Route::get('/pragi/ai-pertanyaan', [PasienController::class, 'getPragiQuestions']);
        Route::get('/pragi/ai/pertanyaan', [PasienController::class, 'getPragiQuestions']);
        Route::post('/pragi/skrining', [PasienController::class, 'submitPragi']);
        Route::post('/pragi/predict', [PasienController::class, 'submitPragiPredict']);
        Route::post('/pragi/prediksi', [PasienController::class, 'submitPragiPredict']);
        Route::get('/pragi/ml-status', [PasienController::class, 'checkPragiMlStatus']);
        Route::post('/pragi/encode', [PasienController::class, 'encodePragiData']);
        Route::get('/pragi/riwayat', [PasienController::class, 'getPragiHistory']);
        Route::get('/pragi/riwayat/{id}', [PasienController::class, 'getPragiDetail']);
        Route::post('/pragi/chat', [PasienController::class, 'chatPragi']);
        Route::get('/pragi/chat/history', [PasienController::class, 'getPragiChatHistory']);
        Route::delete('/pragi/chat/history', [PasienController::class, 'clearPragiChatHistory']);

        // 6. OBAT (Katalog, Beli Umum/Resep, Checkout, Lacak, Batal & Konfirmasi Selesai)
        Route::get('/obat/katalog', [PasienController::class, 'getObatList']);
        Route::get('/obat/katalog/{id}', [PasienController::class, 'getObatDetail']);
        Route::post('/obat/beli-umum', [PasienController::class, 'beliObatUmum']);
        Route::get('/obat/resep-saya', [PasienController::class, 'getResepPasien']);
        Route::post('/obat/beli-resep', [PasienController::class, 'beliObatResep']);
        Route::post('/obat/checkout', [PasienController::class, 'checkoutObat']);
        Route::get('/obat/riwayat-pembelian', [PasienController::class, 'getRiwayatPembelian']);
        Route::get('/obat/pesanan/{id}', [PasienController::class, 'getDetailPesanan']);
        Route::get('/obat/lacak/{id}', [PasienController::class, 'lacakPesanan']);
        Route::post('/obat/pesanan/{id}/batal', [PasienController::class, 'batalPesanan']);
        Route::post('/obat/pesanan/{id}/selesai', [PasienController::class, 'konfirmasiTerimaPesanan']);
        Route::post('/obat/pesanan/{id}/konfirmasi-terima', [PasienController::class, 'konfirmasiTerimaPesanan']);

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
        Route::get('/dokter/{id}/rating', [KonsultasiController::class, 'getDokterUlasan']);
        Route::get('/dokter/{id}/ulasan', [KonsultasiController::class, 'getDokterUlasan']);
        Route::post('/dokter/{id}/rating', [KonsultasiController::class, 'beriRatingDokter']);
        Route::post('/dokter/rating', [KonsultasiController::class, 'beriRatingDokter']);
        Route::post('/booking', [KonsultasiController::class, 'bookingDokter']);
        Route::get('/{id}', [KonsultasiController::class, 'getDetailKonsultasi']);
        Route::post('/{id}/bayar', [KonsultasiController::class, 'bayarKonsultasi']);
        Route::get('/{id}/status-bayar', [KonsultasiController::class, 'cekStatusPembayaran']);

        // Batalkan & Rating Konsultasi
        Route::post('/{id}/batal', [KonsultasiController::class, 'batalKonsultasi']);
        Route::post('/{id}/rating', [KonsultasiController::class, 'beriRatingKonsultasi']);

        // Resep Elektronik dari Konsultasi
        Route::get('/{id}/resep', [KonsultasiController::class, 'getResepKonsultasi']);

        // Chat Konsultasi Realtime
        Route::get('/{id}/pesan', [KonsultasiController::class, 'getMessages']);
        Route::post('/{id}/pesan', [KonsultasiController::class, 'sendMessage']);

        // Video Call Dokter - Pasien
        Route::get('/{id}/video-call', [KonsultasiController::class, 'getVideoCall']);
        Route::post('/{id}/video-call/end', [KonsultasiController::class, 'endVideoCall']);

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
        Route::get('/jadwal/hari-ini', [DokterController::class, 'getJadwalHariIni']);

        // 3. Pasien & Detail Pasien (Pantau harian + PRAGI screening)
        Route::get('/pasien', [DokterController::class, 'getPasienList']);
        Route::get('/pasien/{id}/detail', [DokterController::class, 'getPasienDetail']);

        // 4. Resep Obat Elektronik
        Route::post('/resep', [DokterController::class, 'createResep']);
        Route::get('/resep', [DokterController::class, 'getRiwayatResep']);
        Route::get('/resep/{id}', [DokterController::class, 'getResepDetail']);
        Route::delete('/resep/{id}', [DokterController::class, 'deleteResep']);
        Route::get('/obat-rekomendasi', [DokterController::class, 'getDaftarObatResep']);

        // 5. Profile Dokter & Pengaturan
        Route::get('/profile', [DokterController::class, 'getProfile']);
        Route::put('/profile', [DokterController::class, 'updateProfile']);
        Route::post('/profile/foto', [DokterController::class, 'updateFotoProfil']);
        Route::put('/profile/password', [DokterController::class, 'updatePassword']);
        Route::put('/profile/notifikasi', [DokterController::class, 'updateNotificationSettings']);
        Route::patch('/status-praktik', [DokterController::class, 'toggleStatusPraktik']);

        // 6. Manajemen Perangkat Login (Sanctum Tokens)
        Route::get('/profile/perangkat', [DokterController::class, 'getLoginDevices']);
        Route::delete('/profile/perangkat/{id_token}', [DokterController::class, 'revokeDevice']);

        // 7. Rating & Ulasan Dokter
        Route::get('/{id}/rating', [KonsultasiController::class, 'getDokterUlasan']);
        Route::get('/{id}/ulasan', [KonsultasiController::class, 'getDokterUlasan']);
        Route::post('/{id}/rating', [KonsultasiController::class, 'beriRatingDokter']);
        Route::post('/rating', [KonsultasiController::class, 'beriRatingDokter']);
    });

    // --- E. APOTEK ROUTES ---
    Route::prefix('apotek')->group(function () {
        // 1. Dashboard & Notifikasi
        Route::get('/dashboard', [ApotekController::class, 'getDashboard']);
        Route::get('/notifikasi', [ApotekController::class, 'getNotifikasi']);

        // 2. Reminder Pasien (Melihat jadwal pengingat pasien)
        Route::get('/reminder', [ApotekController::class, 'getReminders']);

        // 3. Pesanan Obat Pasien (Terima, Proses, Selesaikan, Detail, Tracking)
        Route::get('/pesanan', [ApotekController::class, 'getPesananList']);
        Route::get('/pesanan/{id}', [ApotekController::class, 'getPesananDetail']);
        Route::post('/pesanan/{id}/terima', [ApotekController::class, 'terimaPesanan']);
        Route::post('/pesanan/{id}/proses', [ApotekController::class, 'prosesPesanan']);
        Route::post('/pesanan/{id}/selesai', [ApotekController::class, 'selesaikanPesanan']);
        Route::post('/pesanan/{id}/tolak', [ApotekController::class, 'tolakPesanan']);
        Route::post('/pesanan/{id}/tracking', [ApotekController::class, 'addPesananTracking']);

        // 4. Resep Dokter (Pesanan obat resep & validasi resep apoteker)
        Route::get('/resep', [ApotekController::class, 'getPesananResep']);
        Route::post('/resep/{id}/validasi', [ApotekController::class, 'validasiDanTerimaResep']);

        // 5. Obat & Stok Obat (Daftar, Detail, Update, Hapus, Stok, Kadaluwarsa, Batch)
        Route::get('/obat', [ApotekController::class, 'getDaftarObat']);
        Route::get('/obat/stock', [ApotekController::class, 'getStockObat']);
        Route::get('/obat/kadaluwarsa', [ApotekController::class, 'getObatKadaluwarsa']);
        Route::post('/obat', [ApotekController::class, 'tambahObat']);
        Route::get('/obat/{id}', [ApotekController::class, 'getDetailObat']);
        Route::put('/obat/{id}', [ApotekController::class, 'updateObat']);
        Route::delete('/obat/{id}', [ApotekController::class, 'deleteObat']);
        Route::post('/obat/update-stock', [ApotekController::class, 'updateStock']);
        Route::post('/obat/batch', [ApotekController::class, 'tambahBatchObat']);

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
