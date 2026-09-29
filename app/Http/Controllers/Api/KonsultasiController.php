<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Konsultasi;
use App\Models\KonsultasiPesan;
use App\Models\Notifikasi;
use App\Models\Pasien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KonsultasiController extends Controller
{
    /**
     * Resolve actor strictly from Sanctum user token.
     */
    protected function getActor(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof Dokter) {
            return ['type' => 'dokter', 'model' => $user, 'id' => $user->id_dokter];
        }

        if ($user instanceof Pasien) {
            return ['type' => 'pasien', 'model' => $user, 'id' => $user->id_pasien];
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
                  ->orWhere('institusi', 'like', '%' . $request->search . '%');
            });
        }

        $dokters = $query->get([
            'id_dokter', 'nama', 'email', 'spesialisasi', 'biaya_konsultasi', 
            'institusi', 'alamat_praktik', 'foto_profil', 'no_sip', 'no_str'
        ]);

        return $this->successResponse($dokters, 'Daftar dokter tersedia untuk konsultasi');
    }

    /**
     * Get detail dokter by ID.
     */
    public function getDokterDetail(int|string $id): JsonResponse
    {
        $dokter = Dokter::find($id);

        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $totalPasien = Konsultasi::where('id_dokter', $dokter->id_dokter)->distinct('id_pasien')->count('id_pasien');
        $totalKonsultasi = Konsultasi::where('id_dokter', $dokter->id_dokter)->where('status_konsultasi', 'selesai')->count();

        $detail = [
            'dokter' => $dokter,
            'statistik' => [
                'total_pasien_ditangani' => $totalPasien,
                'total_konsultasi_selesai' => $totalKonsultasi,
                'rating' => 4.9,
                'pengalaman_tahun' => 8,
            ],
            'jadwal_praktik' => [
                'Senin - Jumat' => '08:00 - 17:00 WIB',
                'Sabtu' => '09:00 - 14:00 WIB',
            ],
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
            'status_konsultasi' => 'menunggu_pembayaran',
            'status_pembayaran' => 'menunggu_pembayaran',
            'biaya' => $biaya,
            'isi_konsultasi' => $request->isi_konsultasi,
            'catatan_dokter' => null,
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
            'pasien:id_pasien,nama,email,no_hp,jenis_kelamin,foto_profile',
            'dokter:id_dokter,nama,spesialisasi,foto_profil,institusi,biaya_konsultasi',
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
            'status_pembayaran' => 'lunas',
            'status_konsultasi' => 'berlangsung',
            'room_id' => $roomId,
            'waktu_mulai' => Carbon::now(),
        ]);

        // Simpan pesan awal pasien ke riwayat chat
        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'sender_type' => 'pasien',
            'sender_id' => $konsultasi->id_pasien,
            'pesan' => $konsultasi->isi_konsultasi ?? 'Halo dokter, saya ingin berkonsultasi mengenai kondisi ginjal saya.',
            'tipe' => 'text',
        ]);

        // Simpan pesan sistem
        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'sender_type' => 'system',
            'sender_id' => 0,
            'pesan' => "Pembayaran berhasil dikonfirmasi. Sesi konsultasi telah aktif. Anda dapat berkonsultasi melalui chat dan Video Call bersama {$konsultasi->dokter->nama}.",
            'tipe' => 'system',
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
            'dokter:id_dokter,nama,spesialisasi,foto_profil'
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
            ->where('sender_type', '!=', $actor['type'])
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
     */
    public function sendMessage(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::find($id);
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

        $pesan = KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'sender_type' => $actor['type'],
            'sender_id' => $actor['id'],
            'pesan' => $request->pesan,
            'tipe' => $request->input('tipe', 'text'),
            'attachment' => $request->attachment,
            'is_read' => false,
        ]);

        return $this->successResponse($pesan, 'Pesan terkirim', 201);
    }

    /**
     * 6. Video Call: Mengambil room video call / memicu panggilan video call.
     */
    public function getVideoCall(Request $request, int|string $id): JsonResponse
    {
        $konsultasi = Konsultasi::with(['pasien', 'dokter'])->find($id);
        if (! $konsultasi) {
            return $this->errorResponse('Konsultasi tidak ditemukan', 404);
        }

        if ($konsultasi->status_pembayaran !== 'lunas') {
            return $this->errorResponse('Sesi belum dibayar', 402);
        }

        if ($konsultasi->status_konsultasi === 'selesai') {
            return $this->errorResponse('Sesi konsultasi telah selesai', 403);
        }

        if (! $konsultasi->room_id) {
            $konsultasi->update(['room_id' => 'giat-meet-' . Str::uuid()]);
        }

        $videoUrl = "https://meet.jit.si/{$konsultasi->room_id}";
        $actor = $this->getActor($request);

        if ($request->input('notify_chat', false)) {
            KonsultasiPesan::create([
                'id_konsultasi' => $konsultasi->id_konsultasi,
                'sender_type' => $actor['type'],
                'sender_id' => $actor['id'],
                'pesan' => "Panggilan video call dimulai. Klik tautan untuk bergabung: {$videoUrl}",
                'tipe' => 'video_call',
            ]);
        }

        return $this->successResponse([
            'room_id' => $konsultasi->room_id,
            'video_call_url' => $videoUrl,
            'dokter' => $konsultasi->dokter->nama,
            'pasien' => $konsultasi->pasien->nama,
        ], 'Informasi sesi video call');
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

        $catatanDokter = $request->input('catatan_dokter') ?? $konsultasi->catatan_dokter;

        $konsultasi->update([
            'status_konsultasi' => 'selesai',
            'waktu_selesai' => Carbon::now(),
            'catatan_dokter' => $catatanDokter,
        ]);

        KonsultasiPesan::create([
            'id_konsultasi' => $konsultasi->id_konsultasi,
            'sender_type' => 'system',
            'sender_id' => 0,
            'pesan' => "Sesi konsultasi telah selesai. Riwayat konsultasi ini sekarang bersifat hanya-baca (read-only).",
            'tipe' => 'system',
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
            ->with(['dokter:id_dokter,nama,spesialisasi,institusi,foto_profil'])
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
            ->with(['pasien:id_pasien,nama,email,no_hp,jenis_kelamin,foto_profile'])
            ->withCount('pesan')
            ->orderBy('tanggal_konsultasi', 'desc')
            ->get();

        return $this->successResponse($konsultasi, 'Riwayat konsultasi dokter');
    }
}
