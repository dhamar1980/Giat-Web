<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Konsultasi;
use App\Models\KonsultasiPesan;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\Pantau;
use App\Models\Pasien;
use App\Models\Pragi;
use App\Models\ResepItem;
use App\Models\ResepObat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DokterController extends Controller
{
    /**
     * Resolve current Dokter strictly from Sanctum auth user token.
     */
    protected function getAuthenticatedDokter(Request $request): ?Dokter
    {
        $user = $request->user();
        if ($user instanceof Dokter) {
            return $user;
        }

        return null;
    }

    /**
     * 1. Dashboard Dokter: Ringkasan jadwal hari ini, pasien aktif, dan resep.
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $today = Carbon::today();

        $jadwalHariIni = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->whereDate('tanggal_konsultasi', $today)
            ->with(['pasien:id_pasien,nama,jenis_kelamin,foto_profile,no_hp'])
            ->orderBy('tanggal_konsultasi', 'asc')
            ->get();

        $totalPasien = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->distinct('id_pasien')
            ->count('id_pasien');

        $totalKonsultasiSelesai = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->where('status', 'selesai')
            ->count();

        $resepTerbaru = ResepObat::where('id_dokter', $dokter->id_dokter)
            ->with(['pasien:id_pasien,nama', 'obat'])
            ->latest('tanggal_resep')
            ->take(5)
            ->get();

        return $this->successResponse([
            'dokter' => [
                'id_dokter' => $dokter->id_dokter,
                'nama' => $dokter->nama,
                'spesialisasi' => $dokter->spesialisasi,
                'institusi' => $dokter->institusi,
                'foto_profil' => $dokter->foto_profil,
            ],
            'statistik' => [
                'jadwal_hari_ini_count' => $jadwalHariIni->count(),
                'total_pasien_unik' => $totalPasien,
                'total_konsultasi_selesai' => $totalKonsultasiSelesai,
            ],
            'jadwal_hari_ini' => $jadwalHariIni,
            'resep_terbaru' => $resepTerbaru,
        ], 'Ringkasan dashboard dokter');
    }

    /**
     * Notifikasi dokter.
     */
    public function getNotifikasi(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $notifikasi = Notifikasi::where(function ($q) use ($dokter) {
            $q->where('id_user', $dokter->id_dokter)->orWhereNull('id_user');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return $this->successResponse($notifikasi, 'Daftar notifikasi dokter');
    }

    /**
     * 2. JADWAL: Melihat seluruh jadwal konsultasi dokter.
     */
    public function getJadwal(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $query = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->with(['pasien:id_pasien,nama,no_hp,jenis_kelamin,foto_profile'])
            ->orderBy('tanggal_konsultasi', 'asc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->filter === 'today') {
            $query->whereDate('tanggal_konsultasi', Carbon::today());
        } elseif ($request->filter === 'upcoming') {
            $query->where('tanggal_konsultasi', '>=', Carbon::now());
        }

        $jadwal = $query->get();

        return $this->successResponse($jadwal, 'Daftar jadwal konsultasi dokter');
    }

    /**
     * 3. PASIEN: Melihat daftar pasien yang terhubung dengan dokter.
     */
    public function getPasienList(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $pasienIds = Konsultasi::where('id_dokter', $dokter->id_dokter)
            ->distinct()
            ->pluck('id_pasien');

        $pasiens = Pasien::whereIn('id_pasien', $pasienIds)
            ->withCount(['konsultasi' => fn($q) => $q->where('id_dokter', $dokter->id_dokter)])
            ->get();

        return $this->successResponse($pasiens, 'Daftar pasien dokter');
    }

    /**
     * 4. DETAIL PASIEN: Melihat detail pasien (Profil, Catatan Pantau harian, & Skrining PRAGI).
     */
    public function getPasienDetail(int|string $id): JsonResponse
    {
        $pasien = Pasien::find($id);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $pantauHistory = Pantau::where('id_pasien', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $pragiHistory = Pragi::where('id_pasien', $id)
            ->orderBy('tanggal_screening', 'desc')
            ->take(5)
            ->get();

        $resepHistory = ResepObat::where('id_pasien', $id)
            ->with(['obat', 'dokter:id_dokter,nama,spesialisasi'])
            ->orderBy('tanggal_resep', 'desc')
            ->get();

        return $this->successResponse([
            'pasien' => $pasien,
            'catatan_pantau_harian' => $pantauHistory,
            'skrining_pragi' => $pragiHistory,
            'riwayat_resep' => $resepHistory,
        ], 'Detail rekam medis dan data kesehatan pasien');
    }

    /**
     * 5. MEMBUAT RESEP OBAT: Dokter meresepkan obat untuk pasien.
     */
    public function createResep(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'id_pasien' => 'required|exists:pasien,id_pasien',
            'id_obat' => 'required|exists:obat,id_obat',
            'dosis' => 'required|string',
            'id_apotek' => 'nullable|exists:apotek,id_apotek',
            'id_konsultasi' => 'nullable|exists:konsultasi,id_konsultasi',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi resep gagal', 422, $validator->errors());
        }

        $resep = ResepObat::create([
            'id_dokter' => $dokter->id_dokter,
            'id_pasien' => $request->id_pasien,
            'id_konsultasi' => $request->id_konsultasi,
            'tanggal_resep' => Carbon::now()->toDateString(),
            'catatan_dokter' => $request->dosis,
            'status' => 'aktif',
        ]);

        $obat = Obat::find($request->id_obat);
        ResepItem::create([
            'id_resep' => $resep->id,
            'nama_obat' => $obat?->nama_obat ?? 'Obat Resep',
            'dosis' => $request->dosis,
            'aturan_pakai' => $request->dosis,
            'jumlah' => 1,
        ]);

        // Jika dibuat saat sesi konsultasi aktif, otomatis kirim pesan resep ke chat
        if ($request->id_konsultasi) {
            $konsultasi = Konsultasi::find($request->id_konsultasi);
            if ($konsultasi) {
                $namaObat = $obat?->nama_obat ?? 'Obat';
                KonsultasiPesan::create([
                    'id_konsultasi' => $konsultasi->id_konsultasi,
                    'id_sender' => $dokter->id_dokter,
                    'pesan' => "📋 [RESEP ELEKTRONIK DOKTER]:\nObat: {$namaObat}\nDosis & Aturan Pakai: {$request->dosis}\n(Resep telah dikirim ke menu Resep Pasien untuk ditebus di Apotek).",
                ]);
            }
        }

        // Notifikasi ke Pasien
        Notifikasi::create([
            'id_user' => $request->id_pasien,
            'role' => 'pasien',
            'judul' => 'Resep Obat Baru Diterbitkan',
            'pesan' => "{$dokter->nama} telah menerbitkan resep elektronik untuk Anda: {$obat->nama_obat}.",
            'tipe' => 'resep',
            'data' => ['id_resep' => $resep->id_resep],
        ]);

        return $this->successResponse(
            $resep->load(['pasien', 'obat', 'apotek']),
            'Resep obat berhasil dibuat untuk pasien',
            201
        );
    }

    /**
     * Riwayat resep yang telah diterbitkan oleh dokter ini.
     */
    public function getRiwayatResep(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $resep = ResepObat::where('id_dokter', $dokter->id_dokter)
            ->with(['pasien:id_pasien,nama,no_hp,foto_profile', 'obat', 'apotek:id_apotek,nama_apotek'])
            ->orderBy('tanggal_resep', 'desc')
            ->paginate(15);

        return $this->successResponse($resep, 'Riwayat resep elektronik yang diterbitkan');
    }

    /**
     * Rekomendasi daftar obat untuk resep dokter.
     */
    public function getDaftarObatResep(Request $request): JsonResponse
    {
        $query = Obat::query();

        if ($request->has('search')) {
            $query->where('nama_obat', 'like', '%' . $request->search . '%')
                  ->orWhere('kategori', 'like', '%' . $request->search . '%')
                  ->orWhere('deskripsi', 'like', '%' . $request->search . '%');
        }

        if ($request->has('tipe')) {
            $query->where('tipe_obat', $request->tipe);
        }

        $obatList = $query->orderBy('nama_obat', 'asc')->get();

        return $this->successResponse($obatList, 'Daftar obat rekomendasi untuk peresepan');
    }

    // =========================================================================
    // 6. PROFILE DOKTER, NOTIFIKASI, PASSWORD & DEVICE MANAGEMENT
    // =========================================================================

    public function getProfile(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        return $this->successResponse($dokter, 'Data profil dokter');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'no_sip' => 'nullable|string|max:100',
            'no_str' => 'nullable|string|max:100',
            'spesialisasi' => 'nullable|string|in:Dokter Umum,Dokter Spesialis Penyakit Dalam,Dokter Ginjal',
            'biaya_konsultasi' => 'nullable|numeric|min:0',
            'institusi' => 'nullable|string|max:255',
            'alamat_praktik' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi profil dokter gagal', 422, $validator->errors());
        }

        $dokter->update($validator->validated());

        return $this->successResponse($dokter, 'Profil dokter berhasil diperbarui');
    }

    public function updateFotoProfil(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'foto_profil' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi foto profil dokter gagal', 422, $validator->errors());
        }

        $fotoUrl = $request->foto_profil;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foto_dokter', 'public');
            $fotoUrl = url('storage/' . $path);
        }

        $dokter->update(['foto_profil' => $fotoUrl]);

        return $this->successResponse([
            'foto_profil' => $fotoUrl,
            'dokter' => $dokter,
        ], 'Foto profil dokter berhasil diperbarui');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi kata sandi gagal', 422, $validator->errors());
        }

        if (! Hash::check($request->current_password, $dokter->password)) {
            return $this->errorResponse('Kata sandi saat ini tidak cocok', 400);
        }

        $dokter->update(['password' => Hash::make($request->password)]);

        return $this->successResponse(null, 'Kata sandi dokter berhasil diperbarui');
    }

    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'notifikasi_chat_pasien' => 'nullable|boolean',
            'notifikasi_jadwal_baru' => 'nullable|boolean',
            'notifikasi_pengingat_konsultasi' => 'nullable|boolean',
            'notifikasi_email' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi preferensi notifikasi gagal', 422, $validator->errors());
        }

        $settings = array_merge($dokter->notifikasi_settings ?? [], $validator->validated());
        $dokter->update(['notifikasi_settings' => $settings]);

        return $this->successResponse([
            'notifikasi_settings' => $dokter->notifikasi_settings,
        ], 'Preferensi notifikasi berhasil diperbarui');
    }

    public function getLoginDevices(Request $request): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $devices = DB::table('personal_access_tokens')
            ->where('tokenable_type', Dokter::class)
            ->where('tokenable_id', $dokter->id_dokter)
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->orderBy('last_used_at', 'desc')
            ->get();

        return $this->successResponse($devices, 'Daftar perangkat login');
    }

    public function revokeDevice(Request $request, int|string $id_token): JsonResponse
    {
        $dokter = $this->getAuthenticatedDokter($request);
        if (! $dokter) {
            return $this->errorResponse('Dokter tidak ditemukan', 404);
        }

        $deleted = DB::table('personal_access_tokens')
            ->where('tokenable_type', Dokter::class)
            ->where('tokenable_id', $dokter->id_dokter)
            ->where('id', $id_token)
            ->delete();

        if (! $deleted) {
            return $this->errorResponse('Sesi perangkat tidak ditemukan', 404);
        }

        return $this->successResponse(null, 'Perangkat berhasil dikeluarkan (logout paksa)');
    }
}
