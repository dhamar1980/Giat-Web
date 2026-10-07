<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EdukasiKesehatan;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\Pantau;
use App\Models\Pasien;
use App\Models\Pembelian;
use App\Models\Pragi;
use App\Models\Reminder;
use App\Models\ResepObat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasienController extends Controller
{
    /**
     * Resolve currently authenticated Pasien from Sanctum token.
     */
    protected function getAuthenticatedPasien(Request $request): ?Pasien
    {
        $user = $request->user();
        if ($user instanceof Pasien) {
            return $user;
        }

        return null;
    }

    // =========================================================================
    // 1. HOME & EDUKASI KESEHATAN
    // =========================================================================

    /**
     * Get home dashboard data.
     */
    public function getHome(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Akses ditolak. Endpoint ini khusus untuk akun Pasien.', 403);
        }

        $edukasi = EdukasiKesehatan::orderBy('created_at', 'desc')->take(10)->get();
        $latestPantau = Pantau::where('id_pasien', $pasien->id_pasien)->latest()->first();
        $latestPragi = Pragi::where('id_pasien', $pasien->id_pasien)->latest()->first();
        $activeReminders = Reminder::where('id_pasien', $pasien->id_pasien)->where('is_active', true)->take(3)->get();

        return $this->successResponse([
            'pasien' => [
                'id_pasien' => $pasien->id_pasien,
                'nama' => $pasien->nama,
                'foto_profile' => $pasien->foto_profile,
            ],
            'ringkasan_kesehatan' => [
                'pantau_terakhir' => $latestPantau,
                'skrining_pragi_terakhir' => $latestPragi,
            ],
            'pengingat_aktif' => $activeReminders,
            'edukasi_kesehatan' => $edukasi,
        ], 'Berhasil memuat data beranda GIAT');
    }

    /**
     * Get list of health education articles.
     */
    public function getEdukasiList(Request $request): JsonResponse
    {
        $query = EdukasiKesehatan::query();

        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('judul', 'like', '%' . $request->search . '%')
                  ->orWhere('konten', 'like', '%' . $request->search . '%');
            });
        }

        $edukasi = $query->orderBy('created_at', 'desc')->paginate(10);

        return $this->successResponse($edukasi, 'Daftar edukasi kesehatan ginjal');
    }

    /**
     * Get single edukasi kesehatan detail.
     */
    public function getEdukasiDetail(int|string $id): JsonResponse
    {
        $edukasi = EdukasiKesehatan::find($id);
        if (! $edukasi) {
            return $this->errorResponse('Artikel edukasi tidak ditemukan', 404);
        }

        return $this->successResponse($edukasi, 'Detail edukasi kesehatan ginjal');
    }

    // =========================================================================
    // 2. REMINDER (CRUD & TOGGLE)
    // =========================================================================

    public function getReminders(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $reminders = Reminder::where('id_pasien', $pasien->id_pasien)
            ->orderBy('tanggal', 'asc')
            ->orderBy('waktu', 'asc')
            ->get();

        return $this->successResponse($reminders, 'Daftar pengingat minum obat pasien');
    }

    public function createReminder(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'waktu' => 'required',
            'keterangan' => 'nullable|string',
            'pengulangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi data reminder gagal', 422, $validator->errors());
        }

        $reminder = Reminder::create([
            'id_pasien' => $pasien->id_pasien,
            'nama' => $request->nama,
            'tanggal' => $request->tanggal,
            'waktu' => $request->waktu,
            'keterangan' => $request->keterangan,
            'pengulangan' => $request->input('pengulangan', 'Setiap Hari'),
            'is_active' => $request->input('is_active', true),
        ]);

        return $this->successResponse($reminder, 'Reminder berhasil ditambahkan', 201);
    }

    public function updateReminder(Request $request, int|string $id): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        $reminder = Reminder::where('id', $id)
            ->when($pasien, fn($q) => $q->where('id_pasien', $pasien->id_pasien))
            ->first();

        if (! $reminder) {
            return $this->errorResponse('Reminder tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'tanggal' => 'sometimes|required|date',
            'waktu' => 'sometimes|required',
            'keterangan' => 'nullable|string',
            'pengulangan' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi update reminder gagal', 422, $validator->errors());
        }

        $reminder->update($validator->validated());

        return $this->successResponse($reminder, 'Reminder berhasil diperbarui');
    }

    /**
     * Toggle reminder active state.
     */
    public function toggleReminder(Request $request, int|string $id): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        $reminder = Reminder::where('id', $id)
            ->when($pasien, fn($q) => $q->where('id_pasien', $pasien->id_pasien))
            ->first();

        if (! $reminder) {
            return $this->errorResponse('Reminder tidak ditemukan', 404);
        }

        $reminder->is_active = ! $reminder->is_active;
        $reminder->save();

        $statusText = $reminder->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse($reminder, "Pengingat berhasil {$statusText}");
    }

    public function deleteReminder(Request $request, int|string $id): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        $reminder = Reminder::where('id', $id)
            ->when($pasien, fn($q) => $q->where('id_pasien', $pasien->id_pasien))
            ->first();

        if (! $reminder) {
            return $this->errorResponse('Reminder tidak ditemukan', 404);
        }

        $reminder->delete();

        return $this->successResponse(null, 'Reminder berhasil dihapus');
    }

    // =========================================================================
    // 3. PROFILE, KONTAK, PASSWORD & FOTO PROFIL
    // =========================================================================

    public function getProfile(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        return $this->successResponse($pasien, 'Data profil pasien');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|string|in:L,P,Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date',
            'gol_darah' => 'nullable|string|max:5',
            'NIK' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi profil gagal', 422, $validator->errors());
        }

        $pasien->update($validator->validated());

        return $this->successResponse($pasien, 'Profil berhasil diperbarui');
    }

    public function updateNoHp(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'no_hp' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi nomor HP gagal', 422, $validator->errors());
        }

        $pasien->update(['no_hp' => $request->no_hp]);

        return $this->successResponse($pasien, 'Nomor HP berhasil diperbarui');
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => "required|email|max:255|unique:pasien,email,{$pasien->id_pasien},id_pasien",
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi email gagal', 422, $validator->errors());
        }

        $pasien->update(['email' => $request->email]);

        return $this->successResponse($pasien, 'Email berhasil diperbarui');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi kata sandi gagal', 422, $validator->errors());
        }

        if (! Hash::check($request->current_password, $pasien->password)) {
            return $this->errorResponse('Kata sandi saat ini tidak cocok', 400);
        }

        $pasien->update(['password' => Hash::make($request->password)]);

        return $this->successResponse(null, 'Password berhasil diperbarui');
    }

    public function updateFotoProfile(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'foto_profile' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi foto profil gagal', 422, $validator->errors());
        }

        $fotoUrl = $request->foto_profile;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foto_pasien', 'public');
            $fotoUrl = url('storage/' . $path);
        }

        $pasien->update(['foto_profile' => $fotoUrl]);

        return $this->successResponse([
            'foto_profile' => $fotoUrl,
            'pasien' => $pasien,
        ], 'Foto profil berhasil diperbarui');
    }

    // =========================================================================
    // 4. PANTAU (Kondisi Harian & Summary IMT)
    // =========================================================================

    public function getPantauHistory(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $pantau = Pantau::where('id_pasien', $pasien->id_pasien)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($pantau, 'Riwayat pantau harian ginjal & IMT');
    }

    public function storePantau(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'bb' => 'required|numeric|min:20|max:300',
            'tb' => 'required|numeric|min:50|max:250',
            'keluhan' => 'nullable|string|max:255',
            'detail_keluhan' => 'nullable|string',
            'kondisi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'id_reminder' => 'nullable|exists:reminder,id_reminder',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi catatan pantau gagal', 422, $validator->errors());
        }

        // Kalkulasi IMT otomatis: BB (kg) / (TB (m) ^ 2)
        $tbMeter = $request->tb / 100;
        $imt = round($request->bb / ($tbMeter * $tbMeter), 2);

        $kondisi = $request->kondisi;
        if (! $kondisi) {
            $kondisi = match (true) {
                $imt < 18.5 => 'Berat Badan Kurang',
                $imt <= 24.9 => 'Normal / Sehat',
                $imt <= 29.9 => 'Berat Badan Berlebih',
                default => 'Obesitas',
            };
        }

        $pantau = Pantau::create([
            'id_pasien' => $pasien->id_pasien,
            'id_reminder' => $request->id_reminder,
            'imt' => $imt,
            'bb' => $request->bb,
            'tb' => $request->tb,
            'keluhan' => $request->keluhan,
            'detail_keluhan' => $request->detail_keluhan,
            'kondisi' => $kondisi,
            'catatan' => $request->catatan,
        ]);

        return $this->successResponse($pantau, 'Catatan kondisi harian berhasil disimpan', 201);
    }

    /**
     * Get summary & analytics of daily monitoring.
     */
    public function getPantauSummary(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $pantauList = Pantau::where('id_pasien', $pasien->id_pasien)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        $latest = $pantauList->first();
        $totalEntries = $pantauList->count();
        $avgImt = $totalEntries > 0 ? round($pantauList->avg('imt'), 2) : 0;
        $avgBb = $totalEntries > 0 ? round($pantauList->avg('bb'), 1) : 0;

        // Tentukan tren berat badan jika ada minimal 2 catatan
        $trenBb = 'Stabil';
        if ($pantauList->count() >= 2) {
            $lastBb = (float) $pantauList[0]->bb;
            $prevBb = (float) $pantauList[1]->bb;
            if ($lastBb > $prevBb) {
                $trenBb = 'Meningkat (+ ' . round($lastBb - $prevBb, 1) . ' kg)';
            } elseif ($lastBb < $prevBb) {
                $trenBb = 'Menurun (- ' . round($prevBb - $lastBb, 1) . ' kg)';
            }
        }

        $summary = [
            'total_catatan' => $totalEntries,
            'catatan_terakhir' => $latest,
            'rata_rata_imt' => $avgImt,
            'rata_rata_bb' => $avgBb,
            'tren_berat_badan' => $trenBb,
            'status_imt' => $latest ? $latest->kondisi : 'Belum Ada Data',
            'anjuran_kesehatan' => [
                'hidrasi' => 'Konsumsi cairan disesuaikan dengan anjuran dokter (biasanya 1.5 - 2 liter/hari jika tidak ada retensi cairan).',
                'pola_makan' => 'Batasi konsumsi natrium/garam dapur di bawah 2 gram per hari untuk menjaga kestabilan tekanan darah dan fungsi ginjal.',
                'pemantauan' => 'Catat berat badan setiap pagi setelah buang air kecil untuk memantau ada/tidaknya penumpukan cairan tubuh.',
            ],
            'riwayat_terbaru' => $pantauList->take(5),
        ];

        return $this->successResponse($summary, 'Ringkasan data pemantauan harian pasien');
    }

    // =========================================================================
    // 5. PRAGI (Skrining Risiko CKD & Chatbot AI PRAGI)
    // =========================================================================

    public function getPragiQuestions(): JsonResponse
    {
        $questions = [
            ['id' => 1, 'pertanyaan' => 'Apakah Anda sering merasakan nyeri, rasa pegal, atau ngilu di area pinggang belakang?', 'bobot' => 2],
            ['id' => 2, 'pertanyaan' => 'Apakah Anda memiliki riwayat diabetes melitus (gula darah tinggi)?', 'bobot' => 2],
            ['id' => 3, 'pertanyaan' => 'Apakah terdapat pembengkakan pada kaki, pergelangan kaki, atau wajah di pagi hari?', 'bobot' => 2],
            ['id' => 4, 'pertanyaan' => 'Apakah terjadi perubahan frekuensi buang air kecil (terutama lebih sering di malam hari)?', 'bobot' => 1],
            ['id' => 5, 'pertanyaan' => 'Apakah warna air kencing Anda berbusa, keruh, atau tampak kemerahan?', 'bobot' => 2],
            ['id' => 6, 'pertanyaan' => 'Apakah ada anggota keluarga sedarah yang memiliki riwayat penyakit ginjal kronis (CKD)?', 'bobot' => 1],
            ['id' => 7, 'pertanyaan' => 'Apakah Anda sering mengonsumsi obat antinyeri/pereda sakit (NSAID seperti asam mefenamat/ibuprofen) dalam jangka panjang?', 'bobot' => 1],
            ['id' => 8, 'pertanyaan' => 'Apakah Anda sering merasa cepat lelah, lemas, pucat, atau sulit berkonsentrasi (anemia)?', 'bobot' => 1],
            ['id' => 9, 'pertanyaan' => 'Apakah Anda sering mengalami mual, muntah, atau nafsu makan menurun tanpa sebab jelas?', 'bobot' => 1],
            ['id' => 10, 'pertanyaan' => 'Apakah usia Anda saat ini 50 tahun ke atas?', 'bobot' => 1],
        ];

        return $this->successResponse([
            'judul' => 'Kuesioner Skrining Risiko Penyakit Ginjal Kronis (PRAGI - GIAT)',
            'total_pertanyaan' => count($questions),
            'pertanyaan' => $questions,
        ], 'Daftar pertanyaan skrining PRAGI');
    }

    public function submitPragi(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'jawaban' => 'required|array|min:10',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi jawaban skrining gagal', 422, $validator->errors());
        }

        $jawaban = $request->jawaban;
        $skor = 0;
        $bobotMap = [1 => 2, 2 => 2, 3 => 2, 4 => 1, 5 => 2, 6 => 1, 7 => 1, 8 => 1, 9 => 1, 10 => 1];

        foreach ($jawaban as $qId => $ans) {
            $isYes = in_array(strtolower((string) $ans), ['1', 'true', 'ya', 'yes'], true);
            if ($isYes && isset($bobotMap[$qId])) {
                $skor += $bobotMap[$qId];
            }
        }

        if ($skor <= 2) {
            $prediksi = 'Risiko Rendah CKD';
            $rekomendasi = 'Fungsi ginjal Anda saat ini dinilai stabil dan berisiko rendah. Pertahankan pola hidup sehat: minum air putih minimal 2 liter/hari, batasi konsumsi garam dan gula berlebih, hindari konsumsi obat antinyeri berulang tanpa resep, serta lakukan pemeriksaan rutin berkala.';
        } elseif ($skor <= 5) {
            $prediksi = 'Risiko Sedang CKD';
            $rekomendasi = 'Terdeteksi beberapa faktor risiko ginjal (skor: ' . $skor . '). Disarankan untuk membatasi konsumsi makanan olahan/tinggi garam, menjaga kecukupan cairan secara rutin di menu Pantau, serta konsultasikan dengan Dokter Spesialis Penyakit Dalam atau Dokter Ginjal.';
        } else {
            $prediksi = 'Risiko Tinggi CKD';
            $rekomendasi = 'PERINGATAN: Terdeteksi banyak indikator gejala penurunan fungsi ginjal (skor: ' . $skor . '). Segera jadwalkan konsultasi dengan Dokter Ginjal atau Dokter Spesialis Penyakit Dalam melalui menu Konsultasi GIAT.';
        }

        $pragi = Pragi::create([
            'id_pasien' => $pasien->id_pasien,
            'pertanyaan' => json_encode($jawaban),
            'hasil_prediksi' => $prediksi,
            'rekomendasi' => $rekomendasi,
            'tanggal_screening' => Carbon::now(),
        ]);

        // Buat notifikasi jika risiko tinggi
        if ($skor > 5) {
            Notifikasi::create([
                'id_user' => $pasien->id_pasien,
                'role' => 'pasien',
                'judul' => 'Perhatian: Hasil Skrining PRAGI Berisiko Tinggi',
                'pesan' => 'Hasil skrining menunjukkan skor ' . $skor . '. Disarankan untuk segera melakukan konsultasi dengan Dokter Ginjal di aplikasi GIAT.',
                'tipe' => 'pragi',
                'data' => ['id_pragi' => $pragi->id_pragi, 'skor' => $skor],
            ]);
        }

        return $this->successResponse([
            'id_pragi' => $pragi->id_pragi,
            'skor' => $skor,
            'hasil_prediksi' => $prediksi,
            'rekomendasi' => $rekomendasi,
            'tanggal_screening' => $pragi->tanggal_screening,
        ], 'Skrining PRAGI berhasil diproses', 201);
    }

    public function getPragiHistory(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $history = Pragi::where('id_pasien', $pasien->id_pasien)
            ->orderBy('tanggal_screening', 'desc')
            ->take(5)
            ->get();

        return $this->successResponse($history, 'Riwayat skrining PRAGI pasien');
    }

    /**
     * Chatbot AI PRAGI (Asisten Cerdas Ginjal Sehat).
     */
    public function chatPragi(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pesan' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Pesan wajib diisi', 422, $validator->errors());
        }

        $pesanInput = strtolower(trim($request->pesan));
        $pasien = $this->getAuthenticatedPasien($request);

        // Medical Knowledge-Based Pattern Matching for Renal Health
        $reply = '';
        $topik = 'umum';
        $saranDokter = false;
        $saranTindakan = [];

        if (Str::contains($pesanInput, ['halo', 'hai', 'siang', 'malam', 'pagi', 'assalamu', 'sore'])) {
            $nama = $pasien ? $pasien->nama : 'Sobat Ginjal';
            $reply = "Halo {$nama}! Saya PRAGI, asisten kecerdasan buatan Anda seputar kesehatan ginjal di GIAT. Anda dapat menanyakan tentang gejala penyakit ginjal kronis (CKD), panduan asupan cairan & diet rendah garam, arti hasil laboratorium (kreatinin/ureum), atau cara penggunaan pengingat obat. Ada yang bisa saya bantu hari ini?";
            $topik = 'sambutan';
            $saranTindakan = ['Konsultasi Gejala Ginjal', 'Cek Skrining PRAGI', 'Tips Minum Air Putih'];
        } elseif (Str::contains($pesanInput, ['gejala', 'tanda', 'sakit pinggang', 'nyeri', 'bengkak', 'busa', 'kencing'])) {
            $reply = "Gejala umum penurunan fungsi ginjal (CKD) meliputi:\n1. Pembengkakan (edema) pada kaki, pergelangan, atau kelopak mata.\n2. Urine tampak berbusa tebal atau berwarna keruh/kemerahan.\n3. Nyeri tumpul pada area pinggang samping/belakang.\n4. Sering buang air kecil di malam hari (nokturia).\n5. Cepat lelah dan sesak napas akibat penumpukan cairan atau anemia.\n\nJika Anda merasakan salah satu atau lebih gejala di atas, disarankan untuk melakukan skrining kuesioner PRAGI dan berkonsultasi langsung dengan Dokter Ginjal kami di menu Konsultasi.";
            $topik = 'gejala_ckd';
            $saranDokter = true;
            $saranTindakan = ['Mulai Skrining PRAGI', 'Jadwalkan Konsultasi Dokter'];
        } elseif (Str::contains($pesanInput, ['makan', 'diet', 'garam', 'natrium', 'pantangan', 'buah', 'protein'])) {
            $reply = "Prinsip utama diet ramah ginjal:\n1. **Rendah Garam/Natrium**: Batasi konsumsi garam maksimal 1 sendok teh (2.000 mg natrium) per hari untuk mencegah hipertensi dan pembengkakan.\n2. **Kendalikan Asupan Protein**: Konsumsi protein sesuai anjuran dokter (hindari konsumsi suplemen protein tinggi tanpa pengawasan).\n3. **Perhatikan Kalium & Fosfor**: Jika fungsi ginjal sudah menurun, batasi makanan tinggi kalium (pisang, alpukat) dan tinggi fosfor (jeroan, minuman bersoda gelap).\n4. Hindari makanan olahan (fast food, sosis, mie instan kaldu tinggi).";
            $topik = 'nutrisi_diet';
            $saranTindakan = ['Lihat Edukasi Nutrisi Ginjal', 'Catat Menu di Pantau'];
        } elseif (Str::contains($pesanInput, ['minum', 'air', 'cairan', 'berapa liter', 'haus'])) {
            $reply = "Pedoman asupan cairan untuk ginjal:\n- **Untuk ginjal sehat / pencegahan**: Minum air putih 1,5 hingga 2 liter per hari (sekitar 8 gelas).\n- **Untuk pasien CKD stadium lanjut atau yang menjalani dialisis**: Asupan cairan harus dibatasi ketat sesuai anjuran nefrolog (biasanya volume urine 24 jam + 500 ml) guna mencegah overload cairan yang menyebabkan sesak napas dan edema paru.";
            $topik = 'asupan_cairan';
            $saranTindakan = ['Pasang Pengingat Minum Obat/Air', 'Catat Kondisi Harian'];
        } elseif (Str::contains($pesanInput, ['kreatinin', 'ureum', 'egfr', 'lab', 'darah'])) {
            $reply = "Pemeriksaan fungsi ginjal laboratorium meliputi:\n- **Kreatinin Serum**: Nilai normal pria ~0.7-1.3 mg/dL, wanita ~0.6-1.1 mg/dL. Nilai yang meningkat menandakan penurunan laju filtrasi ginjal.\n- **eGFR (Laju Filtrasi Glomerulus)**: Angka di atas 90 menunjukkan fungsi ginjal optimal. Jika di bawah 60 selama lebih dari 3 bulan, dicurigai terjadi CKD.\n- **Ureum/BUN**: Sisa metabolisme protein yang dikeluarkan ginjal.\n\nHarap diskusikan hasil laboratorium Anda dengan dokter spesialis kami agar mendapat diagnosis dan terapi yang akurat.";
            $topik = 'interpretasi_lab';
            $saranDokter = true;
            $saranTindakan = ['Konsultasikan Hasil Lab', 'Unggah Riwayat Medis'];
        } elseif (Str::contains($pesanInput, ['obat', 'antinyeri', 'nsaid', 'paracetamol', 'asam mefenamat', 'ibuprofen'])) {
            $reply = "Hati-hati dalam memilih obat antinyeri! Obat golongan NSAID (seperti asam mefenamat, ibuprofen, natrium diklofenak) yang dikonsumsi terus-menerus tanpa resep dokter dapat menurunkan aliran darah ke ginjal dan memicu gagal ginjal akut (AKI) atau memperburuk CKD.\nUntuk nyeri ringan, parasetamol cenderung lebih aman untuk ginjal, namun tetap harus sesuai dosis anjuran. Selalu konsultasikan sebelum mengonsumsi obat.";
            $topik = 'keamanan_obat';
            $saranTindakan = ['Beli Obat di Katalog GIAT', 'Tanya Resep Dokter'];
        } elseif (Str::contains($pesanInput, ['cuci darah', 'dialisis', 'hemodialisis', 'hd', 'capd'])) {
            $reply = "Hemodialisis (cuci darah) dan CAPD (cuci darah mandiri lewat perut) adalah terapi pengganti fungsi ginjal untuk membantu membuang racun dan kelebihan cairan ketika ginjal berada pada stadium akhir (CKD Stage 5). Dengan manajemen gaya hidup dan kepatuhan pengobatan yang baik, pasien dialisis tetap dapat beraktivitas produktif dan berkualitas.";
            $topik = 'terapi_ginjal';
            $saranDokter = true;
            $saranTindakan = ['Konsultasi Dokter Ginjal', 'Baca Artikel Dialisis'];
        } else {
            $reply = "Pertanyaan Anda sangat menarik. Secara umum, menjaga kesehatan ginjal membutuhkan pemantauan berkala terhadap tekanan darah, kadar gula darah, asupan cairan cukup, dan membatasi konsumsi obat keras tanpa resep.\n\nJika Anda membutuhkan informasi lebih mendalam terkait kondisi spesifik Anda, silakan manfaatkan layanan Telemedisin Konsultasi bersama Dokter Spesialis di GIAT.";
            $topik = 'informasi_umum';
            $saranTindakan = ['Buka Menu Konsultasi', 'Buka Skrining PRAGI'];
        }

        return $this->successResponse([
            'reply' => $reply,
            'topik' => $topik,
            'rekomendasi_dokter' => $saranDokter,
            'saran_tindakan' => $saranTindakan,
            'disclaimer' => 'Informasi dari PRAGI bersifat edukatif dan tidak menggantikan diagnosis klinis dokter spesialis.',
            'waktu' => Carbon::now()->toIso8601String(),
        ], 'Respon asisten AI PRAGI');
    }

    // =========================================================================
    // 6. OBAT (Katalog, Beli Obat Bebas & Resep, Lacak Pesanan)
    // =========================================================================

    public function getObatList(Request $request): JsonResponse
    {
        $query = Obat::query();

        if ($request->has('kategori')) {
            $query->where('kategori', 'like', '%' . $request->kategori . '%');
        }

        if ($request->has('search')) {
            $query->where('nama_obat', 'like', '%' . $request->search . '%');
        }

        $obat = $query->with('apotek')->get();

        return $this->successResponse($obat, 'Daftar obat umum (bebas dan bebas terbatas)');
    }

    /**
     * Get single medicine detail.
     */
    public function getObatDetail(int|string $id): JsonResponse
    {
        $obat = Obat::with('stockObat.apotek')->find($id);

        if (! $obat) {
            return $this->errorResponse('Data obat tidak ditemukan', 404);
        }

        return $this->successResponse($obat, 'Detail informasi obat');
    }

    public function beliObatUmum(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'id_obat' => 'required|exists:obat,id_obat',
            'id_apotek' => 'required|exists:apotek,id_apotek',
            'jumlah' => 'required|integer|min:1',
            'alamat_pengiriman' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi pembelian obat gagal', 422, $validator->errors());
        }

        $obat = Obat::find($request->id_obat);
        $totalHarga = $obat->harga * $request->jumlah;
        $resi = 'GIAT-EXP-' . strtoupper(Str::random(10));

        $pembelian = Pembelian::create([
            'id_pasien' => $pasien->id_pasien,
            'id_obat' => $obat->id_obat,
            'id_resep' => null,
            'id_apotek' => $request->id_apotek,
            'tipe_pembelian' => 'umum',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'menunggu_konfirmasi',
            'total_harga' => $totalHarga,
            'jumlah' => $request->jumlah,
            'alamat_pengiriman' => $request->alamat_pengiriman,
            'catatan' => $request->catatan,
            'nomor_resi' => $resi,
            'kurir' => 'GIAT Express Medika',
            'status_lacak' => [
                [
                    'status' => 'Pesanan Dibuat',
                    'deskripsi' => 'Pesanan obat bebas berhasil dibuat oleh pasien.',
                    'waktu' => Carbon::now()->toIso8601String(),
                ],
            ],
            'tanggal_pembelian' => Carbon::now(),
        ]);

        // Buat notifikasi untuk pasien
        Notifikasi::create([
            'id_user' => $pasien->id_pasien,
            'role' => 'pasien',
            'judul' => 'Pesanan Obat Bebas Dibuat',
            'pesan' => "Pesanan #{$pembelian->id_pembelian} ({$obat->nama_obat}) sedang diteruskan ke apotek.",
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pembelian->id_pembelian],
        ]);

        // Buat notifikasi untuk apotek
        Notifikasi::create([
            'id_user' => $request->id_apotek,
            'role' => 'apotek',
            'judul' => 'Pesanan Obat Baru Masuk',
            'pesan' => "Pesanan baru #{$pembelian->id_pembelian} dari pasien {$pasien->nama}.",
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pembelian->id_pembelian],
        ]);

        return $this->successResponse(
            $pembelian->load(['obat', 'apotek']),
            'Pesanan obat bebas berhasil dibuat dan diteruskan ke apotek',
            201
        );
    }

    public function getResepPasien(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $resep = ResepObat::where('id_pasien', $pasien->id_pasien)
            ->with(['dokter:id_dokter,nama,spesialisasi', 'obat', 'apotek'])
            ->orderBy('tanggal_resep', 'desc')
            ->get();

        return $this->successResponse($resep, 'Daftar resep elektronik pasien');
    }

    public function beliObatResep(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'id_resep' => 'required|exists:resep_obat,id_resep',
            'id_apotek' => 'required|exists:apotek,id_apotek',
            'alamat_pengiriman' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi penebusan resep gagal', 422, $validator->errors());
        }

        $resep = ResepObat::with('obat')->find($request->id_resep);

        if ($resep->id_pasien !== $pasien->id_pasien) {
            return $this->errorResponse('Resep ini bukan milik Anda', 403);
        }

        $obat = $resep->obat;
        $totalHarga = $obat ? $obat->harga : 50000;
        $resi = 'GIAT-RSP-' . strtoupper(Str::random(10));

        $pembelian = Pembelian::create([
            'id_pasien' => $pasien->id_pasien,
            'id_obat' => $resep->id_obat,
            'id_resep' => $resep->id_resep,
            'id_apotek' => $request->id_apotek,
            'tipe_pembelian' => 'resep',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'menunggu_konfirmasi',
            'total_harga' => $totalHarga,
            'jumlah' => 1,
            'alamat_pengiriman' => $request->alamat_pengiriman,
            'catatan' => $request->catatan,
            'nomor_resi' => $resi,
            'kurir' => 'GIAT Express Medika (Apoteker)',
            'status_lacak' => [
                [
                    'status' => 'Pengajuan Resep Dibuat',
                    'deskripsi' => 'Penebusan resep dokter diajukan ke apotek untuk verifikasi apoteker.',
                    'waktu' => Carbon::now()->toIso8601String(),
                ],
            ],
            'tanggal_pembelian' => Carbon::now(),
        ]);

        // Notifikasi ke Apotek
        Notifikasi::create([
            'id_user' => $request->id_apotek,
            'role' => 'apotek',
            'judul' => 'Penebusan Resep Dokter Masuk',
            'pesan' => "Pasien {$pasien->nama} mengajukan penebusan resep #{$resep->id_resep}.",
            'tipe' => 'resep',
            'data' => ['id_pembelian' => $pembelian->id_pembelian, 'id_resep' => $resep->id_resep],
        ]);

        return $this->successResponse(
            $pembelian->load(['resepObat.dokter', 'obat', 'apotek']),
            'Penebusan obat resep dokter berhasil dibuat dan diteruskan ke apotek',
            201
        );
    }

    public function getRiwayatPembelian(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $pembelian = Pembelian::where('id_pasien', $pasien->id_pasien)
            ->with(['obat', 'resepObat.dokter', 'apotek'])
            ->orderBy('tanggal_pembelian', 'desc')
            ->get();

        return $this->successResponse($pembelian, 'Riwayat pembelian obat pasien');
    }

    /**
     * Get detail pesanan obat by ID.
     */
    public function getDetailPesanan(int|string $id, Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);

        $pesanan = Pembelian::with(['obat', 'resepObat.dokter', 'apotek', 'pasien'])
            ->when($pasien, fn($q) => $q->where('id_pasien', $pasien->id_pasien))
            ->find($id);

        if (! $pesanan) {
            return $this->errorResponse('Detail pesanan tidak ditemukan', 404);
        }

        return $this->successResponse($pesanan, 'Detail pesanan obat');
    }

    /**
     * Lacak status pengiriman pesanan obat.
     */
    public function lacakPesanan(int|string $id, Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);

        $pesanan = Pembelian::with(['obat', 'apotek'])
            ->when($pasien, fn($q) => $q->where('id_pasien', $pasien->id_pasien))
            ->find($id);

        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        // Timeline pelacakan dinamis berdasarkan status_pesanan
        $timeline = $pesanan->status_lacak ?? [];
        if (empty($timeline)) {
            $timeline = [
                [
                    'status' => 'Pesanan Dibuat',
                    'deskripsi' => 'Pesanan obat telah diterima oleh sistem GIAT.',
                    'waktu' => $pesanan->tanggal_pembelian ? $pesanan->tanggal_pembelian->toIso8601String() : $pesanan->created_at->toIso8601String(),
                    'selesai' => true,
                ],
                [
                    'status' => 'Konfirmasi Apotek',
                    'deskripsi' => in_array($pesanan->status_pesanan, ['diproses', 'dikirim', 'selesai'])
                        ? 'Pesanan telah dikonfirmasi dan stok obat dialokasikan.'
                        : 'Menunggu konfirmasi apoteker.',
                    'waktu' => in_array($pesanan->status_pesanan, ['diproses', 'dikirim', 'selesai']) ? $pesanan->updated_at->toIso8601String() : null,
                    'selesai' => in_array($pesanan->status_pesanan, ['diproses', 'dikirim', 'selesai']),
                ],
                [
                    'status' => 'Pengemasan & Pengiriman',
                    'deskripsi' => in_array($pesanan->status_pesanan, ['dikirim', 'selesai'])
                        ? 'Paket obat sedang dibawa oleh kurir menuju alamat Anda.'
                        : 'Obat sedang disiapkan dan dikemas secara higienis.',
                    'waktu' => in_array($pesanan->status_pesanan, ['dikirim', 'selesai']) ? $pesanan->updated_at->toIso8601String() : null,
                    'selesai' => in_array($pesanan->status_pesanan, ['dikirim', 'selesai']),
                ],
                [
                    'status' => 'Pesanan Selesai',
                    'deskripsi' => $pesanan->status_pesanan === 'selesai'
                        ? 'Obat telah sampai di tangan pasien. Selamat lekas sehat!'
                        : 'Pesanan belum selesai.',
                    'waktu' => $pesanan->status_pesanan === 'selesai' ? $pesanan->updated_at->toIso8601String() : null,
                    'selesai' => $pesanan->status_pesanan === 'selesai',
                ],
            ];
        }

        $trackingData = [
            'id_pembelian' => $pesanan->id_pembelian,
            'nomor_resi' => $pesanan->nomor_resi ?? ('GIAT-' . str_pad($pesanan->id_pembelian, 8, '0', STR_PAD_LEFT)),
            'kurir' => $pesanan->kurir ?? 'GIAT Medika Express',
            'status_pesanan' => $pesanan->status_pesanan,
            'status_pembayaran' => $pesanan->status_pembayaran,
            'obat' => $pesanan->obat ? $pesanan->obat->nama_obat : 'Obat Resep',
            'apotek' => $pesanan->apotek ? $pesanan->apotek->nama : 'Apotek Rekanan',
            'alamat_pengiriman' => $pesanan->alamat_pengiriman,
            'timeline' => $timeline,
        ];

        return $this->successResponse($trackingData, 'Data pelacakan pengiriman pesanan');
    }

    // =========================================================================
    // 7. NOTIFIKASI PASIEN
    // =========================================================================

    public function getNotifikasi(Request $request): JsonResponse
    {
        $pasien = $this->getAuthenticatedPasien($request);
        if (! $pasien) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        }

        $notifikasi = Notifikasi::where(function ($q) use ($pasien) {
            $q->where('id_user', $pasien->id_pasien)->orWhereNull('id_user');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return $this->successResponse($notifikasi, 'Daftar notifikasi pasien');
    }
}
