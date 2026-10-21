<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Konsultasi;
use App\Models\KonsultasiPembayaran;
use App\Models\KonsultasiPesan;
use App\Models\KonsultasiVideoSession;
use App\Models\Notifikasi;
use App\Models\Pasien;
use App\Models\ResepObat;
use App\Models\UlasanDokter;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KonsultasiController extends Controller
{
    /**
     * Resolve actor strictly from Sanctum user token.
     */
    protected function getActor(Request $request): array
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user instanceof Dokter) {
            return ['type' => 'dokter', 'model' => $user, 'id' => $user->id_dokter];
        }

        if ($user instanceof Pasien) {
            return ['type' => 'pasien', 'model' => $user, 'id' => $user->id_pasien];
        }

        if ($user instanceof User) {
            if ($user->role === 'dokter' || $user->dokter) {
                $dokter = $user->dokter ?? Dokter::find($user->id);
                return ['type' => 'dokter', 'model' => $dokter ?? $user, 'id' => $dokter?->id_dokter ?? $user->id];
            }
            $pasien = $user->pasien ?? Pasien::find($user->id);
            return ['type' => 'pasien', 'model' => $pasien ?? $user, 'id' => $pasien?->id_pasien ?? $user->id];
        }

        return ['type' => null, 'model' => null, 'id' => null];
    }

    /**
     * Kategori spesialisasi dokter yang tersedia di aplikasi GIAT.
     */
    public function getKategoriDokter(): JsonResponse
    {
        $kategori = [
            'Dokter Umum',
            'Dokter Spesialis Penyakit Dalam',
            'Dokter Ginjal',
        ];

        return $this->successResponse($kategori, 'Daftar kategori spesialisasi dokter di aplikasi GIAT');
    }

    /**
     * 1. Get available doctors for booking.
     */
    public function getDokterList(Request $request): JsonResponse
    {
        $query = Dokter::query();

        $kategori = $request->input('kategori') ?? $request->input('spesialisasi');
        if ($kategori) {
            $query->where('spesialisasi', 'like', '%' . $kategori . '%');
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('spesialisasi', 'like', '%' . $request->search . '%')
                  ->orWhere('instansi', 'like', '%' . $request->search . '%');
            });
        }

        $dokters = $query->with('user')->get();

        return $this->successResponse($dokters, 'Daftar dokter tersedia untuk konsultasi');
    }

    /**
     * Get detail dokter by ID.
     */
    public function getDokterDetail(int|string $id): JsonResponse
    {
        $dokter = Dokter::find($id);

        if (! $dokter) {
            $dokter = Dokter::where('nama', 'ilike', '%' . str_replace('_', ' ', $id) . '%')->first();
        }

        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $totalPasien = Konsultasi::where('id_dokter', $dokter->id_dokter)->distinct('id_pasien')->count('id_pasien');
        $totalKonsultasi = Konsultasi::where('id_dokter', $dokter->id_dokter)->where('status', 'selesai')->count();

        // Ambil ulasan dan rating riil dari tabel ulasan_dokter
        $ulasanQuery = UlasanDokter::where('id_dokter', $dokter->id_dokter)
            ->orWhere('id_dokter', (string) $id);
        $totalUlasan = (clone $ulasanQuery)->count();
        $avgRating = (clone $ulasanQuery)->avg('rating');
        $rating = $totalUlasan > 0 ? round((float) $avgRating, 1) : 4.9;

        $detail = [
            'dokter' => $dokter,
            'statistik' => [
                'total_pasien_ditangani' => $totalPasien,
                'total_konsultasi_selesai' => $totalKonsultasi,
                'total_ulasan' => $totalUlasan,
                'rating' => $rating,
                'pengalaman_tahun' => 8,
            ],
            'jadwal_praktik' => [
                'Senin - Jumat' => '08:00 - 17:00 WIB',
                'Sabtu' => '09:00 - 14:00 WIB',
            ],
            'ulasan_terbaru' => (clone $ulasanQuery)->latest('created_at')->take(5)->get(),
        ];

        return $this->successResponse($detail, 'Detail profil dokter');
    }

    /**
     * 2. Booking dokter: menentukan jadwal tanggal konsultasi dan keluhan awal.
     */
    public function bookingDokter(Request $request): JsonResponse
    {
        $actor = $this->getActor($request);
        $pasien = $actor['type'] === 'pasien' ? $actor['model'] : null;

        if (! $pasien) {
            return $this->errorResponse('Akses ditolak. Booking dokter hanya dapat dilakukan oleh akun Pasien.', 403);
        }

        $validator = Validator::make($request->all(), [
            'id_dokter' => 'required|exists:dokter,id_dokter',
            'tanggal_konsultasi' => 'required|date|after_or_equal:today',
            'isi_konsultasi' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi booking dokter gagal', 422, $validator->errors());
        }

        $dokter = Dokter::find($request->id_dokter);
        $biaya = $dokter->biaya_konsultasi ?? 50000;

        $konsultasi = Konsultasi::create([
            'id_pasien' => $pasien->id_pasien,
            'id_dokter' => $dokter->id_dokter,
            'tanggal_konsultasi' => Carbon::parse($request->tanggal_konsultasi),
            'status' => 'menunggu_pembayaran',
            'keluhan_awal' => $request->isi_konsultasi,
            'jenis_layanan' => 'chat',
        ]);

        KonsultasiPembayaran::create([
            'id_konsultasi' => $konsultasi->id,
            'no_invoice' => 'INV-' . strtoupper(Str::random(10)),
            'jumlah_bayar' => $biaya,
            'metode_pembayaran' => 'transfer_bank',
            'status_bayar' => 'menunggu_pembayaran',
        ]);

        // Kirim notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pasien->id_pasien,
            'role' => 'pasien',
            'judul' => 'Jadwal Konsultasi Dibuat',
            'pesan' => "Booking dengan {$dokter->nama} berhasil dibuat. Silakan selesaikan pembayaran Rp " . number_format($biaya, 0, ',', '.') . ".",
            'tipe' => 'konsultasi',
            'data' => ['id_konsultasi' => $konsultasi->id_konsultasi],
        ]);

        // Kirim notifikasi ke dokter
        Notifikasi::create([
            'id_user' => $dokter->id_dokter,
            'role' => 'dokter',
            'judul' => 'Permintaan Konsultasi Baru',
            'pesan' => "Pasien {$pasien->nama} telah melakukan booking jadwal konsultasi pada " . $konsultasi->tanggal_konsultasi->format('d M Y H:i') . ".",
            'tipe' => 'konsultasi',
            'data' => ['id_konsultasi' => $konsultasi->id_konsultasi],
        ]);

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'dokter' => [
                'id_dokter' => $dokter->id_dokter,
                'nama' => $dokter->nama,
                'spesialisasi' => $dokter->spesialisasi,
                'foto_profil' => $dokter->foto_profil,
            ],
            'jadwal' => $konsultasi->tanggal_konsultasi->format('Y-m-d H:i'),
            'biaya' => $biaya,
            'status_konsultasi' => $konsultasi->status_konsultasi,
            'status_pembayaran' => $konsultasi->status_pembayaran,
        ], 'Booking jadwal dokter berhasil. Silakan selesaikan pembayaran untuk memulai konsultasi.', 201);
    }

    /**
     * Get detail sesi konsultasi.
     */
    public function getDetailKonsultasi(int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with([
            'pasien:id_pasien,nama,no_hp,jenis_kelamin,foto_profile',
            'dokter:id_dokter,nama,spesialisasi,foto_profile,instansi,tarif_konsultasi',
        ])->find($id);

        if (! $konsultasi) {
            return $this->errorResponse('Data konsultasi tidak ditemukan', 404);
        }

        return $this->successResponse($konsultasi, 'Detail informasi konsultasi');
    }

    /**
     * 3. Melakukan payment / pembayaran konsultasi.
     */
    public function bayarKonsultasi(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Data konsultasi tidak ditemukan', 404);
        }

        if ($konsultasi->status_pembayaran === 'lunas') {
            return $this->successResponse($konsultasi, 'Konsultasi ini sudah dibayar sebelumnya');
        }

        $validator = Validator::make($request->all(), [
            'metode_pembayaran' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi pembayaran gagal', 422, $validator->errors());
        }

        $roomId = 'giat-meet-' . Str::uuid();

        $konsultasi->update([
            'status' => 'berlangsung',
            'jam_mulai' => Carbon::now()->toTimeString(),
        ]);

        KonsultasiPembayaran::updateOrCreate(
            ['id_konsultasi' => $konsultasi->id],
            [
                'no_invoice' => 'INV-' . strtoupper(Str::random(10)),
                'jumlah_bayar' => $konsultasi->biaya ?? 50000,
                'metode_pembayaran' => $request->input('metode_pembayaran', 'qris'),
                'status_bayar' => 'lunas',
                'waktu_bayar' => Carbon::now(),
            ]
        );

        KonsultasiVideoSession::updateOrCreate(
            ['id_konsultasi' => $konsultasi->id],
            [
                'room_id' => $roomId,
                'status_panggilan' => 'berlangsung',
            ]
        );

        // Simpan pesan awal pasien ke riwayat chat
        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id,
            'id_sender' => $konsultasi->id_pasien,
            'pesan' => $konsultasi->keluhan_awal ?? 'Halo dokter, saya ingin berkonsultasi mengenai kondisi ginjal saya.',
        ]);

        // Simpan pesan sistem
        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id,
            'id_sender' => $konsultasi->id_dokter,
            'pesan' => "Pembayaran berhasil dikonfirmasi. Sesi konsultasi telah aktif. Anda dapat berkonsultasi melalui chat dan Video Call bersama {$konsultasi->dokter->nama}.",
        ]);

        // Notifikasi ke dokter
        Notifikasi::create([
            'id_user' => $konsultasi->id_dokter,
            'role' => 'dokter',
            'judul' => 'Sesi Konsultasi Telah Aktif',
            'pesan' => "Pembayaran dari pasien {$konsultasi->pasien->nama} telah diterima. Sesi chat dan video call telah dimulai.",
            'tipe' => 'konsultasi',
            'data' => ['id_konsultasi' => $konsultasi->id_konsultasi, 'room_id' => $roomId],
        ]);

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'status_pembayaran' => 'lunas',
            'status_konsultasi' => 'berlangsung',
            'room_id' => $roomId,
            'video_call_url' => "https://meet.jit.si/{$roomId}",
        ], 'Pembayaran berhasil! Sesi konsultasi dan fitur video call telah aktif.');
    }

    /**
     * Cek status pembayaran sesi konsultasi.
     */
    public function cekStatusPembayaran(int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);

        if (! $konsultasi) {
            return $this->errorResponse('Data konsultasi tidak ditemukan', 404);
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'status_pembayaran' => $konsultasi->status_pembayaran,
            'status_konsultasi' => $konsultasi->status_konsultasi,
            'biaya' => $konsultasi->biaya,
            'room_id' => $konsultasi->room_id,
            'video_call_url' => $konsultasi->room_id ? "https://meet.jit.si/{$konsultasi->room_id}" : null,
        ], 'Status pembayaran sesi konsultasi');
    }

    /**
     * 4. Mengambil riwayat pesan chat konsultasi.
     */
    public function getMessages(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with([
            'pasien:id_pasien,nama,foto_profile',
            'dokter:id_dokter,nama,spesialisasi,foto_profile'
        ])->find($id);

        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $actor = $this->getActor($request);

        if ($konsultasi->status_pembayaran !== 'lunas') {
            return $this->errorResponse('Sesi chat belum aktif. Silakan lakukan pembayaran terlebih dahulu.', 402);
        }

        // Tandai pesan terbaca
        KonsultasiPesan::where('id_konsultasi', $id)
            ->where('id_sender', '!=', $actor['id'])
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = KonsultasiPesan::where('id_konsultasi', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $isReadOnly = ($konsultasi->status_konsultasi === 'selesai' || $konsultasi->status_konsultasi === 'dibatalkan');

        return $this->successResponse([
            'konsultasi' => [
                'id_konsultasi' => $konsultasi->id_konsultasi,
                'status_konsultasi' => $konsultasi->status_konsultasi,
                'is_read_only' => $isReadOnly,
                'tanggal_konsultasi' => $konsultasi->tanggal_konsultasi,
                'room_id' => $konsultasi->room_id,
                'video_call_url' => $konsultasi->room_id ? "https://meet.jit.si/{$konsultasi->room_id}" : null,
                'pasien' => $konsultasi->pasien,
                'dokter' => $konsultasi->dokter,
                'catatan_dokter' => $konsultasi->catatan_dokter,
            ],
            'messages' => $messages,
        ], 'Riwayat pesan konsultasi');
    }

    /**
     * 5. Mengirim pesan chat (Konsep WhatsApp) antara pasien dan dokter.
     * Tersimpan di database lokal & otomatis dipush ke Firebase Realtime Database.
     */
    public function sendMessage(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        if ($konsultasi->status_pembayaran !== 'lunas') {
            return $this->errorResponse('Harap selesaikan pembayaran terlebih dahulu.', 402);
        }

        if ($konsultasi->status_konsultasi === 'selesai') {
            return $this->errorResponse('Sesi konsultasi telah selesai. Riwayat chat bersifat hanya-baca (read-only).', 403);
        }

        $validator = Validator::make($request->all(), [
            'pesan' => 'required|string',
            'tipe' => 'nullable|string|in:text,image,resep,video_call',
            'attachment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi pesan gagal', 422, $validator->errors());
        }

        $actor = $this->getActor($request);
        if (! $actor['type']) {
            return $this->errorResponse('Akses ditolak. Anda harus login sebagai Pasien atau Dokter untuk mengirim pesan.', 403);
        }

        $tipe = $request->input('tipe', 'text');
        $attachment = $request->input('attachment');

        $pesan = KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'id_sender' => $actor['id'],
            'pesan' => $request->pesan,
            'attachment_url' => $attachment,
            'is_read' => false,
        ]);

        $senderName = $actor['model']?->nama ?? ($actor['type'] === 'dokter' ? 'Dokter' : 'Pasien');

        // Push ke Firebase Realtime Database node: konsultasi/{id}/messages
        try {
            /** @var FirebaseService $firebase */
            $firebase = app(FirebaseService::class);
            $firebase->pushRealtimeMessage($konsultasi->id_konsultasi, [
                'id' => $pesan->id,
                'id_sender' => (string) $actor['id'],
                'sender_name' => $senderName,
                'sender_role' => $actor['type'],
                'pesan' => $pesan->pesan,
                'tipe' => $tipe,
                'attachment_url' => $pesan->attachment_url,
                'created_at' => $pesan->created_at?->toDateTimeString() ?? now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Push pesan ke Firebase RTDB gagal: ' . $e->getMessage());
        }

        return $this->successResponse($pesan, 'Pesan terkirim', 201);
    }

    /**
     * 5b. Indikator sedang mengetik (Typing Indicator) pada chat konsultasi.
     */
    public function typingIndicator(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'is_typing' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi status mengetik gagal', 422, $validator->errors());
        }

        $actor = $this->getActor($request);
        if (! $actor['type']) {
            return $this->errorResponse('Akses ditolak. Anda harus login sebagai Pasien atau Dokter.', 403);
        }

        $role = $actor['type']; // 'pasien' | 'dokter'
        $isTyping = filter_var($request->is_typing, FILTER_VALIDATE_BOOLEAN);

        try {
            /** @var FirebaseService $firebase */
            $firebase = app(FirebaseService::class);
            $firebase->updateTypingStatus($konsultasi->id_konsultasi, $role, $isTyping);
        } catch (\Throwable $e) {
            Log::warning('Gagal mengupdate typing status ke Firebase: ' . $e->getMessage());
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'role' => $role,
            'is_typing' => $isTyping,
        ], 'Status sedang mengetik berhasil diperbarui');
    }

    /**
     * 5c. Dapatkan informasi Channel / Path Realtime Database Firebase untuk sesi konsultasi.
     */
    public function getFirebaseChannel(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        /** @var FirebaseService $firebase */
        $firebase = app(FirebaseService::class);
        $channelInfo = $firebase->getFirebaseChannelInfo($konsultasi->id_konsultasi);

        return $this->successResponse($channelInfo, 'Konfigurasi channel realtime Firebase');
    }

    /**
     * 6. Video Call: Mengambil status room video call Jitsi dan kredensial.
     */
    public function getVideoCall(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter', 'videoSession'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        if ($konsultasi->status_pembayaran !== 'lunas') {
            return $this->errorResponse('Sesi belum dibayar', 402);
        }

        if ($konsultasi->status_konsultasi === 'selesai') {
            return $this->errorResponse('Sesi konsultasi telah selesai', 403);
        }

        $videoSession = $konsultasi->videoSession;
        if (! $videoSession || ! $videoSession->room_id) {
            $roomId = 'giat-room-' . Str::uuid();
            $videoSession = KonsultasiVideoSession::updateOrCreate(
                ['id_konsultasi' => $konsultasi->id],
                ['room_id' => $roomId, 'status_panggilan' => 'idle']
            );
            $konsultasi->load('videoSession');
        } else {
            $roomId = $videoSession->room_id;
        }

        $videoUrl = "https://meet.jit.si/{$roomId}";
        $actor = $this->getActor($request);

        if ($request->input('notify_chat', false)) {
            $pesan = KonsultasiPesan::create([
                'id_konsultasi' => $konsultasi->id_konsultasi,
                'id_sender' => $actor['id'] ?? $konsultasi->id_dokter,
                'pesan' => "Panggilan video call dimulai. Klik tautan untuk bergabung: {$videoUrl}",
            ]);

            try {
                /** @var FirebaseService $firebase */
                $firebase = app(FirebaseService::class);
                $firebase->pushRealtimeMessage($konsultasi->id_konsultasi, [
                    'id' => $pesan->id,
                    'id_sender' => (string) ($actor['id'] ?? $konsultasi->id_dokter),
                    'sender_name' => $actor['model']?->nama ?? 'Pengguna',
                    'sender_role' => $actor['type'] ?? 'dokter',
                    'pesan' => $pesan->pesan,
                    'tipe' => 'video_call',
                    'attachment_url' => $videoUrl,
                    'created_at' => now()->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'room_id' => $roomId,
            'video_call_url' => $videoUrl,
            'status_panggilan' => $videoSession->status_panggilan ?? 'idle',
            'dokter' => $konsultasi->dokter?->nama,
            'pasien' => $konsultasi->pasien?->nama,
        ], 'Informasi sesi video call');
    }

    /**
     * 6b. Inisiasi panggilan video (Start Video Call) dengan signaling ringing.
     */
    public function startVideoCall(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        if ($konsultasi->status_pembayaran !== 'lunas') {
            return $this->errorResponse('Sesi belum dibayar. Harap selesaikan pembayaran terlebih dahulu.', 402);
        }

        if ($konsultasi->status_konsultasi === 'selesai' || $konsultasi->status_konsultasi === 'dibatalkan') {
            return $this->errorResponse("Konsultasi tidak aktif (status: {$konsultasi->status_konsultasi})", 403);
        }

        $actor = $this->getActor($request);
        if (! $actor['type']) {
            return $this->errorResponse('Akses ditolak. Anda harus login sebagai Pasien atau Dokter.', 403);
        }

        $roomId = 'giat-room-' . Str::uuid();
        $videoUrl = "https://meet.jit.si/{$roomId}";

        KonsultasiVideoSession::updateOrCreate(
            ['id_konsultasi' => $konsultasi->id],
            [
                'room_id' => $roomId,
                'status_panggilan' => 'ringing',
            ]
        );

        $callerName = $actor['model']?->nama ?? ($actor['type'] === 'dokter' ? 'Dokter' : 'Pasien');

        // Push signal ringing ke Firebase RTDB
        $signalData = [
            'status' => 'ringing',
            'caller_id' => (string) $actor['id'],
            'caller_name' => $callerName,
            'caller_role' => $actor['type'],
            'room_id' => $roomId,
            'timestamp' => Carbon::now()->timestamp,
        ];

        try {
            /** @var FirebaseService $firebase */
            $firebase = app(FirebaseService::class);
            $firebase->updateCallSignal($konsultasi->id_konsultasi, $signalData);
        } catch (\Throwable $e) {
            Log::warning('Gagal update call signal ke Firebase: ' . $e->getMessage());
        }

        // Catat pesan chat panggilan masuk
        if ($request->input('notify_chat', true)) {
            $pesan = KonsultasiPesan::create([
                'id_konsultasi' => $konsultasi->id_konsultasi,
                'id_sender' => $actor['id'],
                'pesan' => "Panggilan video call dimulai oleh {$callerName}. Menunggu jawaban...",
            ]);

            try {
                $firebase = app(FirebaseService::class);
                $firebase->pushRealtimeMessage($konsultasi->id_konsultasi, [
                    'id' => $pesan->id,
                    'id_sender' => (string) $actor['id'],
                    'sender_name' => $callerName,
                    'sender_role' => $actor['type'],
                    'pesan' => $pesan->pesan,
                    'tipe' => 'video_call',
                    'attachment_url' => $videoUrl,
                    'created_at' => now()->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'room_id' => $roomId,
            'video_call_url' => $videoUrl,
            'status_panggilan' => 'ringing',
            'caller' => [
                'id' => (string) $actor['id'],
                'nama' => $callerName,
                'role' => $actor['type'],
            ],
            'dokter' => $konsultasi->dokter?->nama,
            'pasien' => $konsultasi->pasien?->nama,
        ], 'Panggilan video call berhasil diinisiasi. Menunggu jawaban lawan bicara.', 201);
    }

    /**
     * 6c. Respons sinyal video call (accept, reject, busy).
     */
    public function signalVideoCall(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter', 'videoSession'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:accept,reject,busy,connected,rejected,ended,ringing',
            'signal' => 'nullable|string|in:accept,reject,busy,connected,rejected,ended,ringing',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi sinyal video call gagal', 422, $validator->errors());
        }

        $rawSignal = $request->input('signal') ?? $request->input('status');
        if (! $rawSignal) {
            return $this->errorResponse('Parameter signal atau status wajib diisi (accept, reject, busy, ended)', 422);
        }

        $mappedStatus = match ($rawSignal) {
            'accept', 'connected' => 'connected',
            'reject', 'rejected' => 'rejected',
            'busy' => 'busy',
            'ended' => 'ended',
            default => 'ringing',
        };

        $actor = $this->getActor($request);
        $responderName = $actor['model']?->nama ?? 'Pengguna GIAT';

        $videoSession = $konsultasi->videoSession;
        $roomId = $videoSession?->room_id ?? ($konsultasi->room_id ?? 'giat-room-' . Str::uuid());

        KonsultasiVideoSession::updateOrCreate(
            ['id_konsultasi' => $konsultasi->id],
            [
                'room_id' => $roomId,
                'status_panggilan' => $mappedStatus,
            ]
        );

        $signalData = [
            'status' => $mappedStatus,
            'responder_id' => (string) ($actor['id'] ?? ''),
            'responder_name' => $responderName,
            'responder_role' => $actor['type'] ?? 'user',
            'room_id' => $roomId,
            'timestamp' => Carbon::now()->timestamp,
        ];

        try {
            /** @var FirebaseService $firebase */
            $firebase = app(FirebaseService::class);
            $firebase->updateCallSignal($konsultasi->id_konsultasi, $signalData);
        } catch (\Throwable $e) {
            Log::warning('Gagal update sinyal video call ke Firebase: ' . $e->getMessage());
        }

        if (in_array($mappedStatus, ['rejected', 'busy'])) {
            $pesanText = $mappedStatus === 'rejected'
                ? "Panggilan video ditolak oleh {$responderName}."
                : "Panggilan video tidak dapat diangkat ({$responderName} sedang sibuk).";

            $pesan = KonsultasiPesan::create([
                'id_konsultasi' => $konsultasi->id_konsultasi,
                'id_sender' => $actor['id'] ?? $konsultasi->id_dokter,
                'pesan' => $pesanText,
            ]);

            try {
                $firebase = app(FirebaseService::class);
                $firebase->pushRealtimeMessage($konsultasi->id_konsultasi, [
                    'id' => $pesan->id,
                    'id_sender' => (string) ($actor['id'] ?? $konsultasi->id_dokter),
                    'sender_name' => $responderName,
                    'sender_role' => $actor['type'] ?? 'user',
                    'pesan' => $pesanText,
                    'tipe' => 'video_call',
                    'attachment_url' => null,
                    'created_at' => now()->toDateTimeString(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'status' => $mappedStatus,
            'room_id' => $roomId,
            'video_call_url' => "https://meet.jit.si/{$roomId}",
            'responder' => [
                'id' => (string) ($actor['id'] ?? ''),
                'nama' => $responderName,
                'role' => $actor['type'] ?? 'user',
            ],
        ], 'Sinyal panggilan video berhasil diproses');
    }

    /**
     * 7. Menyelesaikan sesi konsultasi (oleh Dokter atau Pasien).
     */
    public function selesaikanKonsultasi(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $konsultasi->update([
            'status' => 'selesai',
            'jam_selesai' => Carbon::now()->toTimeString(),
        ]);

        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'id_sender' => $konsultasi->id_dokter,
            'pesan' => "Sesi konsultasi telah selesai. Riwayat konsultasi ini sekarang bersifat hanya-baca (read-only).",
        ]);

        return $this->successResponse($konsultasi, 'Sesi konsultasi telah selesai. Riwayat chat telah diarsipkan.');
    }

    /**
     * 8. Riwayat konsultasi untuk Pasien.
     */
    public function getPasienKonsultasi(Request $request): JsonResponse
    {
        $actor = $this->getActor($request);
        $pasien = $actor['type'] === 'pasien' ? $actor['model'] : null;

        if (! $pasien) {
            return $this->errorResponse('Akses ditolak. Riwayat ini khusus untuk akun Pasien.', 403);
        }

        $konsultasi = Konsultasi::where('id_pasien', $pasien->id_pasien)
            ->with(['dokter:id_dokter,nama,spesialisasi,institusi,foto_profile'])
            ->withCount('pesan')
            ->orderBy('tanggal_konsultasi', 'desc')
            ->get();

        return $this->successResponse($konsultasi, 'Riwayat konsultasi pasien');
    }

    /**
     * 9. Jadwal & Riwayat konsultasi untuk Dokter.
     */
    public function getDokterKonsultasi(Request $request): JsonResponse
    {
        $actor = $this->getActor($request);
        $dokter = $actor['type'] === 'dokter' ? $actor['model'] : null;

        if (! $dokter) {
            return $this->errorResponse('Akses ditolak. Riwayat ini khusus untuk akun Dokter.', 403);
        }

        $konsultasi = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->with(['pasien:id_pasien,nama,no_hp,jenis_kelamin,foto_profile'])
            ->withCount('pesan')
            ->orderBy('tanggal_konsultasi', 'desc')
            ->get();

        return $this->successResponse($konsultasi, 'Riwayat konsultasi dokter');
    }

    /**
     * 10. Cancel an upcoming consultation appointment.
     */
    public function batalKonsultasi(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        if (in_array($konsultasi->status, ['selesai', 'dibatalkan'])) {
            return $this->errorResponse("Konsultasi tidak dapat dibatalkan karena sudah dalam status {$konsultasi->status}", 400);
        }

        $alasan = $request->input('alasan', 'Dibatalkan oleh pengguna');
        $konsultasi->update([
            'status' => 'dibatalkan',
        ]);

        // Simpan pesan di riwayat konsultasi
        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id,
            'id_sender' => $konsultasi->id_pasien,
            'pesan' => "Sesi konsultasi telah dibatalkan. Alasan: {$alasan}",
        ]);

        // Notifikasi ke dokter
        Notifikasi::create([
            'id_user' => $konsultasi->id_dokter,
            'role' => 'dokter',
            'judul' => 'Jadwal Konsultasi Dibatalkan',
            'pesan' => "Sesi konsultasi dengan pasien {$konsultasi->pasien->nama} telah dibatalkan.",
            'tipe' => 'konsultasi',
            'data' => ['id_konsultasi' => $konsultasi->id],
        ]);

        return $this->successResponse($konsultasi, 'Konsultasi berhasil dibatalkan');
    }

    /**
     * 11. Patient leaves a rating and review after consultation finishes.
     */
    public function beriRatingKonsultasi(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'ulasan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi rating gagal', 422, $validator->errors());
        }

        $konsultasi->update([
            'rating' => (int) $request->rating,
            'ulasan' => $request->ulasan,
        ]);

        // Sinkronisasi ke tabel ulasan_dokter di Supabase
        $actor = $this->getActor($request);
        $idPasien = $request->input('id_pasien') ?? $konsultasi->id_pasien ?? $actor['id'];
        $namaPasien = $request->input('nama_pasien') ?? $konsultasi->pasien?->nama ?? ($actor['model']?->nama ?? null);

        $ulasanDokter = UlasanDokter::updateOrCreate(
            ['id_konsultasi' => (string) $konsultasi->id],
            [
                'id_dokter' => (string) $konsultasi->id_dokter,
                'id_pasien' => $idPasien ? (string) $idPasien : null,
                'nama_pasien' => $namaPasien ?: 'Pasien Anonim',
                'rating' => (int) $request->rating,
                'ulasan' => $request->ulasan,
            ]
        );

        return $this->successResponse([
            'id' => $ulasanDokter->id,
            'id_konsultasi' => $konsultasi->id,
            'id_dokter' => $konsultasi->id_dokter,
            'id_pasien' => $ulasanDokter->id_pasien,
            'nama_pasien' => $ulasanDokter->nama_pasien,
            'rating' => (int) $request->rating,
            'ulasan' => $request->ulasan,
            'created_at' => $ulasanDokter->created_at?->toISOString() ?? now()->toISOString(),
        ], 'Terima kasih atas ulasan dan rating yang Anda berikan kepada dokter!');
    }

    /**
     * Simpan Rating & Ulasan Dokter (Direct dari Profil Dokter atau Sesi Konsultasi).
     */
    public function beriRatingDokter(Request $request, ?string $id = null): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'ulasan' => 'nullable|string|max:1000',
            'id_dokter' => 'nullable|string',
            'dokter_id' => 'nullable|string',
            'id_pasien' => 'nullable',
            'nama_pasien' => 'nullable|string|max:255',
            'id_konsultasi' => 'nullable',
            'konsultasi_id' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi rating dokter gagal', 422, $validator->errors());
        }

        // Resolusi ID Dokter: Prioritas route parameter {id}, fallback request body
        $targetDokterId = $id ?? $request->input('id_dokter') ?? $request->input('dokter_id');

        if (! $targetDokterId) {
            return $this->errorResponse('ID Dokter wajib diisi', 422, [
                'id_dokter' => ['ID Dokter tidak boleh kosong.'],
            ]);
        }

        // Cari data dokter di database jika ada
        $dokter = Dokter::find($targetDokterId);
        if (! $dokter) {
            $dokter = Dokter::where('nama', 'ilike', '%' . str_replace('_', ' ', $targetDokterId) . '%')->first();
        }

        $idDokterDb = $dokter ? $dokter->id_dokter : (string) $targetDokterId;

        // Resolusi Actor / Pasien login
        $actor = $this->getActor($request);
        $idPasien = $request->input('id_pasien') ?? ($actor['type'] === 'pasien' ? $actor['id'] : null);
        $namaPasien = $request->input('nama_pasien') ?? ($actor['model']?->nama ?? null);

        if (! $namaPasien && $idPasien) {
            $pasienData = Pasien::find($idPasien);
            $namaPasien = $pasienData?->nama;
        }

        // Cek jika ada id_konsultasi untuk sinkronisasi balik ke tabel konsultasi
        $idKonsultasi = $request->input('id_konsultasi') ?? $request->input('konsultasi_id');
        if ($idKonsultasi) {
            $konsultasi = Konsultasi::find($idKonsultasi);
            if ($konsultasi) {
                $konsultasi->update([
                    'rating' => (int) $request->rating,
                    'ulasan' => $request->ulasan,
                ]);

                if (! $idDokterDb) {
                    $idDokterDb = (string) $konsultasi->id_dokter;
                }
                if (! $idPasien) {
                    $idPasien = (string) $konsultasi->id_pasien;
                }
                if (! $namaPasien && $konsultasi->pasien) {
                    $namaPasien = $konsultasi->pasien->nama;
                }
            }
        }

        // Simpan ke tabel ulasan_dokter
        $matchCriteria = [];
        if ($idKonsultasi) {
            $matchCriteria['id_konsultasi'] = (string) $idKonsultasi;
        } elseif ($idPasien) {
            $matchCriteria = [
                'id_dokter' => (string) $idDokterDb,
                'id_pasien' => (string) $idPasien,
            ];
        }

        $payload = [
            'id_dokter' => (string) $idDokterDb,
            'id_pasien' => $idPasien ? (string) $idPasien : null,
            'nama_pasien' => $namaPasien ?: 'Pasien Anonim',
            'id_konsultasi' => $idKonsultasi ? (string) $idKonsultasi : null,
            'rating' => (int) $request->rating,
            'ulasan' => $request->ulasan,
        ];

        if (! empty($matchCriteria)) {
            $ulasanDokter = UlasanDokter::updateOrCreate($matchCriteria, $payload);
        } else {
            $ulasanDokter = UlasanDokter::create($payload);
        }

        return $this->successResponse([
            'id' => $ulasanDokter->id,
            'id_dokter' => $ulasanDokter->id_dokter,
            'id_pasien' => $ulasanDokter->id_pasien,
            'nama_pasien' => $ulasanDokter->nama_pasien,
            'id_konsultasi' => $ulasanDokter->id_konsultasi,
            'rating' => $ulasanDokter->rating,
            'ulasan' => $ulasanDokter->ulasan,
            'created_at' => $ulasanDokter->created_at?->toISOString() ?? now()->toISOString(),
        ], 'Penilaian dokter berhasil disimpan.');
    }

    /**
     * Dapatkan daftar ulasan dan statistik rating untuk dokter tertentu.
     */
    public function getDokterUlasan(Request $request, int|string $id): JsonResponse
    {
        $dokter = Dokter::find($id);
        if (! $dokter) {
            $dokter = Dokter::where('nama', 'ilike', '%' . str_replace('_', ' ', $id) . '%')->first();
        }

        $targetId = $dokter ? $dokter->id_dokter : (string) $id;

        $ulasanList = UlasanDokter::where('id_dokter', $targetId)
            ->orWhere('id_dokter', (string) $id)
            ->latest('created_at')
            ->get();

        $avgRating = $ulasanList->avg('rating');
        $totalUlasan = $ulasanList->count();

        $distribusi = [
            5 => $ulasanList->where('rating', 5)->count(),
            4 => $ulasanList->where('rating', 4)->count(),
            3 => $ulasanList->where('rating', 3)->count(),
            2 => $ulasanList->where('rating', 2)->count(),
            1 => $ulasanList->where('rating', 1)->count(),
        ];

        return $this->successResponse([
            'id_dokter' => $targetId,
            'nama_dokter' => $dokter?->nama,
            'rating_rata_rata' => $totalUlasan > 0 ? round((float) $avgRating, 1) : 5.0,
            'total_ulasan' => $totalUlasan,
            'distribusi_bintang' => $distribusi,
            'daftar_ulasan' => $ulasanList,
        ], 'Daftar ulasan dokter berhasil diambil');
    }

    /**
     * 12. Get e-prescription generated in this consultation session.
     */
    public function getResepKonsultasi(int|string $id): JsonResponse
    {
        $resep = ResepObat::where('id_konsultasi', $id)
            ->with(['items', 'obat', 'dokter:id_dokter,nama,spesialisasi,no_sip,no_str', 'pasien:id_pasien,nama'])
            ->first();

        if (! $resep) {
            return $this->errorResponse('Belum ada resep obat yang diterbitkan pada sesi konsultasi ini', 404);
        }

        return $this->successResponse($resep, 'Resep elektronik dari sesi konsultasi');
    }

    /**
     * 13. Payment Gateway Webhook / Callback Simulator (Midtrans / Xendit).
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id') ?? $request->input('no_invoice') ?? $request->input('id_konsultasi');
        $transactionStatus = $request->input('transaction_status', 'settlement');

        if (! $orderId) {
            return $this->errorResponse('Parameter order_id atau no_invoice wajib disertakan', 422);
        }

        $pembayaran = KonsultasiPembayaran::where('no_invoice', $orderId)
            ->orWhere('id_konsultasi', $orderId)
            ->first();

        if (! $pembayaran) {
            return $this->errorResponse('Data invoice pembayaran konsultasi tidak ditemukan', 404);
        }

        $isLunas = in_array(strtolower($transactionStatus), ['settlement', 'capture', 'success', 'lunas', 'paid']);

        if ($isLunas) {
            $pembayaran->update([
                'status_bayar' => 'lunas',
                'waktu_bayar' => Carbon::now(),
            ]);

            $konsultasi = Konsultasi::find($pembayaran->id_konsultasi);
            if ($konsultasi) {
                $konsultasi->update([
                    'status' => 'berlangsung',
                    'jam_mulai' => Carbon::now()->toTimeString(),
                ]);
            }
        }

        return $this->successResponse([
            'no_invoice' => $pembayaran->no_invoice,
            'status_bayar' => $pembayaran->status_bayar,
        ], 'Callback notifikasi pembayaran berhasil diproses');
    }

    /**
     * 14. End video call and store duration.
     * Mengakhiri panggilan, mencatat durasi di DB & push sinyal ended ke Firebase.
     */
    public function endVideoCall(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter', 'videoSession'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        $durasi = (int) $request->input('durasi_menit', 15);
        $actor = $this->getActor($request);
        $endedBy = $actor['model']?->nama ?? 'Pengguna GIAT';
        $roomId = $konsultasi->videoSession?->room_id ?? ($konsultasi->room_id ?? 'giat-room-' . Str::uuid());

        $videoSession = KonsultasiVideoSession::updateOrCreate(
            ['id_konsultasi' => $konsultasi->id],
            [
                'room_id' => $roomId,
                'status_panggilan' => 'berakhir',
                'durasi_menit' => $durasi,
            ]
        );

        // Update call signal di Firebase RTDB
        try {
            /** @var FirebaseService $firebase */
            $firebase = app(FirebaseService::class);
            $firebase->updateCallSignal($konsultasi->id_konsultasi, [
                'status' => 'ended',
                'durasi_menit' => $durasi,
                'ended_by' => $endedBy,
                'timestamp' => Carbon::now()->timestamp,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal update status call ended ke Firebase: ' . $e->getMessage());
        }

        $pesanText = "Panggilan video call telah berakhir (Durasi: {$durasi} menit).";
        $pesan = KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'id_sender' => $actor['id'] ?? $konsultasi->id_dokter,
            'pesan' => $pesanText,
        ]);

        try {
            $firebase = app(FirebaseService::class);
            $firebase->pushRealtimeMessage($konsultasi->id_konsultasi, [
                'id' => $pesan->id,
                'id_sender' => (string) ($actor['id'] ?? $konsultasi->id_dokter),
                'sender_name' => $endedBy,
                'sender_role' => $actor['type'] ?? 'dokter',
                'pesan' => $pesanText,
                'tipe' => 'video_call',
                'attachment_url' => null,
                'created_at' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        return $this->successResponse([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'status_panggilan' => 'berakhir',
            'durasi_menit' => $durasi,
        ], 'Sesi video call telah diakhiri');
    }
}
