<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apotek;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\Pembelian;
use App\Models\Reminder;
use App\Models\ResepObat;
use App\Models\StockObat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApotekController extends Controller
{
    /**
     * Resolve current Apotek strictly from Sanctum auth user token.
     */
    protected function getAuthenticatedApotek(Request $request): ?Apotek
    {
        $user = $request->user();
        if ($user instanceof Apotek) {
            return $user;
        }

        return null;
    }

    /**
     * Dashboard ringkasan untuk Apotek.
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $pesananMenunggu = Pembelian::where(function ($q) use ($apotek) {
            $q->where('id_apotek', $apotek->id_apotek)->orWhereNull('id_apotek');
        })->where('status_pesanan', 'menunggu_konfirmasi')->count();

        $pesananDiproses = Pembelian::where('id_apotek', $apotek->id_apotek)
            ->whereIn('status_pesanan', ['diproses', 'dikirim'])
            ->count();

        $pesananSelesai = Pembelian::where('id_apotek', $apotek->id_apotek)
            ->where('status_pesanan', 'selesai')
            ->count();

        $totalResep = Pembelian::where('id_apotek', $apotek->id_apotek)
            ->where('tipe_pembelian', 'resep')
            ->count();

        $totalStockItem = StockObat::where('id_apoteker', $apotek->id_apotek)->count();
        $stockMenipis = StockObat::where('id_apoteker', $apotek->id_apotek)
            ->where('jumlah_stock', '<=', 10)
            ->with('obat:id_obat,nama_obat,kategori,harga')
            ->get();

        return $this->successResponse([
            'apotek' => [
                'id_apotek' => $apotek->id_apotek,
                'nama' => $apotek->nama,
                'lokasi_apotek' => $apotek->lokasi_apotek,
                'jam_operasional' => $apotek->jam_operasional,
                'status_layanan' => $apotek->status_layanan ?? 'buka',
            ],
            'ringkasan' => [
                'pesanan_menunggu_konfirmasi' => $pesananMenunggu,
                'pesanan_diproses_dikirim' => $pesananDiproses,
                'pesanan_selesai' => $pesananSelesai,
                'total_pesanan_resep' => $totalResep,
                'total_item_obat' => $totalStockItem,
                'jumlah_stock_menipis' => $stockMenipis->count(),
            ],
            'stock_menipis' => $stockMenipis,
        ], 'Berhasil memuat ringkasan dashboard apotek');
    }

    /**
     * Notifikasi apotek.
     */
    public function getNotifikasi(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $notifikasi = Notifikasi::where(function ($q) use ($apotek) {
            $q->where('id_user', $apotek->id_apotek)->where('role', 'apotek');
        })->orWhere(function ($q) {
            $q->whereNull('id_user')->whereIn('role', ['apotek', 'all']);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return $this->successResponse($notifikasi, 'Daftar notifikasi apotek');
    }

    // =========================================================================
    // AKTIVITAS 1: REMINDER
    // =========================================================================

    /**
     * Apotek dapat melihat daftar reminder pasien (jadwal minum obat & pantau ginjal).
     */
    public function getReminders(Request $request): JsonResponse
    {
        $query = Reminder::with(['pasien:id_pasien,nama,no_hp,email']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('pasien', function ($p) use ($search) {
                      $p->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $reminders = $query->orderBy('tanggal', 'desc')->orderBy('waktu', 'asc')->paginate(15);

        return $this->successResponse($reminders, 'Berhasil memuat data reminder pasien');
    }

    // =========================================================================
    // AKTIVITAS 2: PESANAN (Menerima, Proses, Selesaikan, Detail, Tolak)
    // =========================================================================

    public function getPesananList(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $query = Pembelian::where(function ($q) use ($apotek) {
            $q->where('id_apotek', $apotek->id_apotek)
              ->orWhereNull('id_apotek');
        })->with([
            'pasien:id_pasien,nama,no_hp,email,alamat',
            'obat:id_obat,nama_obat,kategori,tipe_obat,harga',
            'resepObat.dokter:id_dokter,nama,spesialisasi',
        ]);

        if ($request->filled('status_pesanan')) {
            $query->where('status_pesanan', $request->status_pesanan);
        }

        if ($request->filled('tipe_pembelian')) {
            $query->where('tipe_pembelian', $request->tipe_pembelian);
        }

        $pesanan = $query->orderBy('tanggal_pembelian', 'desc')->paginate(15);

        return $this->successResponse($pesanan, 'Berhasil memuat daftar pesanan apotek');
    }

    public function getPesananDetail($id, Request $request): JsonResponse
    {
        $pesanan = Pembelian::with([
            'pasien:id_pasien,nama,no_hp,email,alamat',
            'obat',
            'resepObat.dokter:id_dokter,nama,spesialisasi,no_str',
            'apotek:id_apotek,nama,lokasi_apotek',
        ])->find($id);

        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        return $this->successResponse($pesanan, 'Berhasil memuat detail pesanan');
    }

    public function terimaPesanan($id, Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $pesanan = Pembelian::find($id);
        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        if ($pesanan->status_pesanan === 'selesai') {
            return $this->errorResponse('Pesanan ini sudah selesai', 400);
        }

        if ($pesanan->status_pesanan === 'dibatalkan') {
            return $this->errorResponse('Pesanan ini sudah dibatalkan', 400);
        }

        // Cek dan kurangi stok obat apotek
        $stock = StockObat::where('id_apoteker', $apotek->id_apotek)
            ->where('id_obat', $pesanan->id_obat)
            ->first();

        $jumlahBeli = $pesanan->jumlah ?? 1;

        if ($stock) {
            if ($stock->jumlah_stock < $jumlahBeli) {
                return $this->errorResponse("Stok obat tidak mencukupi (Tersisa: {$stock->jumlah_stock}, Dipesan: {$jumlahBeli})", 422);
            }
            $stock->decrement('jumlah_stock', $jumlahBeli);
        }

        $pesanan->id_apotek = $apotek->id_apotek;
        $pesanan->status_pesanan = 'diproses';

        // Update tracking status
        $timeline = $pesanan->status_lacak ?? [];
        $timeline[] = [
            'status' => 'Diterima Apotek',
            'deskripsi' => "Pesanan diterima oleh {$apotek->nama} dan sedang dipersiapkan.",
            'waktu' => Carbon::now()->toIso8601String(),
        ];
        $pesanan->status_lacak = $timeline;
        $pesanan->save();

        // Notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pesanan->id_pasien,
            'role' => 'pasien',
            'judul' => 'Pesanan Diterima Apotek',
            'pesan' => "Pesanan #{$pesanan->id_pembelian} telah diterima oleh {$apotek->nama} dan sedang disiapkan.",
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pesanan->id_pembelian],
        ]);

        return $this->successResponse(
            $pesanan->fresh(['pasien', 'obat', 'resepObat']),
            'Pesanan berhasil diterima dan sedang dipersiapkan oleh apotek'
        );
    }

    public function prosesPesanan($id, Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        $pesanan = Pembelian::find($id);

        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'status_pesanan' => 'required|in:diproses,dikirim',
            'catatan' => 'nullable|string',
            'nomor_resi' => 'nullable|string',
            'kurir' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi proses pesanan gagal', 422, $validator->errors());
        }

        if ($apotek && ! $pesanan->id_apotek) {
            $pesanan->id_apotek = $apotek->id_apotek;
        }

        $pesanan->status_pesanan = $request->status_pesanan;
        if ($request->filled('catatan')) {
            $pesanan->catatan = ($pesanan->catatan ? $pesanan->catatan . ' | ' : '') . $request->catatan;
        }
        if ($request->filled('nomor_resi')) {
            $pesanan->nomor_resi = $request->nomor_resi;
        }
        if ($request->filled('kurir')) {
            $pesanan->kurir = $request->kurir;
        }

        $timeline = $pesanan->status_lacak ?? [];
        $timeline[] = [
            'status' => $request->status_pesanan === 'dikirim' ? 'Dalam Pengiriman' : 'Sedang Diproses',
            'deskripsi' => $request->status_pesanan === 'dikirim' 
                ? "Paket obat telah diserahkan ke kurir (" . ($pesanan->kurir ?? 'GIAT Medika') . " - Resi: " . ($pesanan->nomor_resi ?? '-') . ")."
                : "Obat sedang dikemas dengan rapi oleh farmasis apotek.",
            'waktu' => Carbon::now()->toIso8601String(),
        ];
        $pesanan->status_lacak = $timeline;
        $pesanan->save();

        // Notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pesanan->id_pasien,
            'role' => 'pasien',
            'judul' => $request->status_pesanan === 'dikirim' ? 'Pesanan Sedang Dikirim' : 'Pesanan Sedang Diproses',
            'pesan' => "Pesanan #{$pesanan->id_pembelian} berstatus: " . ucfirst($request->status_pesanan),
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pesanan->id_pembelian],
        ]);

        return $this->successResponse($pesanan, 'Status proses pesanan berhasil diperbarui');
    }

    public function selesaikanPesanan($id, Request $request): JsonResponse
    {
        $pesanan = Pembelian::find($id);
        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        $pesanan->status_pesanan = 'selesai';
        $pesanan->status_pembayaran = 'lunas';

        $timeline = $pesanan->status_lacak ?? [];
        $timeline[] = [
            'status' => 'Pesanan Selesai',
            'deskripsi' => 'Paket obat telah diterima oleh pasien. Transaksi selesai.',
            'waktu' => Carbon::now()->toIso8601String(),
        ];
        $pesanan->status_lacak = $timeline;
        $pesanan->save();

        // Notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pesanan->id_pasien,
            'role' => 'pasien',
            'judul' => 'Pesanan Telah Selesai',
            'pesan' => "Pesanan #{$pesanan->id_pembelian} telah sampai. Semoga lekas sembuh!",
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pesanan->id_pembelian],
        ]);

        return $this->successResponse($pesanan, 'Pesanan telah berhasil diselesaikan');
    }

    public function tolakPesanan($id, Request $request): JsonResponse
    {
        $pesanan = Pembelian::find($id);
        if (! $pesanan) {
            return $this->errorResponse('Pesanan tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'alasan_penolakan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi penolakan gagal', 422, $validator->errors());
        }

        // Kembalikan stok jika sebelumnya sudah diproses
        if ($pesanan->status_pesanan === 'diproses' && $pesanan->id_apotek) {
            $stock = StockObat::where('id_apoteker', $pesanan->id_apotek)
                ->where('id_obat', $pesanan->id_obat)
                ->first();
            if ($stock) {
                $stock->increment('jumlah_stock', $pesanan->jumlah ?? 1);
            }
        }

        $pesanan->status_pesanan = 'dibatalkan';
        $pesanan->catatan = ($pesanan->catatan ? $pesanan->catatan . ' | ' : '') . 'Ditolak: ' . $request->alasan_penolakan;

        $timeline = $pesanan->status_lacak ?? [];
        $timeline[] = [
            'status' => 'Pesanan Dibatalkan/Ditolak',
            'deskripsi' => 'Alasan: ' . $request->alasan_penolakan,
            'waktu' => Carbon::now()->toIso8601String(),
        ];
        $pesanan->status_lacak = $timeline;
        $pesanan->save();

        // Notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pesanan->id_pasien,
            'role' => 'pasien',
            'judul' => 'Pesanan Dibatalkan',
            'pesan' => "Pesanan #{$pesanan->id_pembelian} dibatalkan. Alasan: {$request->alasan_penolakan}",
            'tipe' => 'pesanan',
            'data' => ['id_pembelian' => $pesanan->id_pembelian],
        ]);

        return $this->successResponse($pesanan, 'Pesanan berhasil ditolak/dibatalkan');
    }

    // =========================================================================
    // AKTIVITAS 3: RESEP (Menerima pesanan obat melalui resep dokter)
    // =========================================================================

    public function getPesananResep(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $pesananResep = Pembelian::where(function ($q) use ($apotek) {
            $q->where('id_apotek', $apotek->id_apotek)->orWhereNull('id_apotek');
        })
        ->where('tipe_pembelian', 'resep')
        ->with([
            'pasien:id_pasien,nama,no_hp,email,alamat',
            'obat:id_obat,nama_obat,kategori,tipe_obat,harga,dosis',
            'resepObat.dokter:id_dokter,nama,spesialisasi,no_str',
        ])
        ->orderBy('tanggal_pembelian', 'desc')
        ->paginate(15);

        return $this->successResponse($pesananResep, 'Berhasil memuat daftar pesanan resep dokter');
    }

    public function validasiDanTerimaResep($id, Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $pesanan = Pembelian::where('tipe_pembelian', 'resep')->with('resepObat.obat')->find($id);
        if (! $pesanan) {
            return $this->errorResponse('Pesanan resep tidak ditemukan', 404);
        }

        if (! $pesanan->resepObat) {
            return $this->errorResponse('Data resep dokter tidak terlampir pada pesanan ini', 422);
        }

        // Cek ketersediaan stok
        $stock = StockObat::where('id_apoteker', $apotek->id_apotek)
            ->where('id_obat', $pesanan->id_obat)
            ->first();

        $jumlahBeli = $pesanan->jumlah ?? 1;

        if (! $stock || $stock->jumlah_stock < $jumlahBeli) {
            return $this->errorResponse('Stok obat resep di apotek Anda tidak mencukupi untuk memenuhi pesanan ini', 422);
        }

        $stock->decrement('jumlah_stock', $jumlahBeli);

        // Update resep obat apoteker penanggung jawab
        $pesanan->resepObat->id_apoteker = $apotek->id_apotek;
        $pesanan->resepObat->save();

        $pesanan->id_apotek = $apotek->id_apotek;
        $pesanan->status_pesanan = 'diproses';
        $pesanan->catatan = ($pesanan->catatan ? $pesanan->catatan . ' | ' : '') . 'Resep telah divalidasi oleh Apoteker: ' . $apotek->nama;

        $timeline = $pesanan->status_lacak ?? [];
        $timeline[] = [
            'status' => 'Resep Divalidasi Apoteker',
            'deskripsi' => "Resep dokter telah diverifikasi oleh Apoteker {$apotek->nama}. Obat keras siap diracik/disiapkan.",
            'waktu' => Carbon::now()->toIso8601String(),
        ];
        $pesanan->status_lacak = $timeline;
        $pesanan->save();

        // Notifikasi ke pasien
        Notifikasi::create([
            'id_user' => $pesanan->id_pasien,
            'role' => 'pasien',
            'judul' => 'Resep Divalidasi Apoteker',
            'pesan' => "Resep Anda telah divalidasi oleh {$apotek->nama} dan pesanan sedang disiapkan.",
            'tipe' => 'resep',
            'data' => ['id_pembelian' => $pesanan->id_pembelian],
        ]);

        return $this->successResponse(
            $pesanan->fresh(['pasien', 'obat', 'resepObat.dokter']),
            'Resep dokter berhasil divalidasi dan pesanan obat keras siap dipersiapkan'
        );
    }

    // =========================================================================
    // AKTIVITAS 4: OBAT & STOK OBAT
    // =========================================================================

    public function getDaftarObat(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        $apotekId = $apotek ? $apotek->id_apotek : null;

        $query = Obat::query();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('tipe_obat')) {
            $query->where('tipe_obat', $request->tipe_obat);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_obat', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $obatList = $query->orderBy('nama_obat', 'asc')->get();

        $data = $obatList->map(function ($obat) use ($apotekId) {
            $stock = $apotekId ? StockObat::where('id_apoteker', $apotekId)->where('id_obat', $obat->id_obat)->first() : null;
            $jumlahStock = $stock ? $stock->jumlah_stock : 0;

            return [
                'id_obat' => $obat->id_obat,
                'nama_obat' => $obat->nama_obat,
                'deskripsi' => $obat->deskripsi,
                'harga' => $obat->harga,
                'kategori' => $obat->kategori,
                'tipe_obat' => $obat->tipe_obat ?? 'bebas',
                'dosis' => $obat->dosis,
                'efek_samping' => $obat->efek_samping,
                'gambar' => $obat->gambar,
                'stock_apotek' => $jumlahStock,
                'status_stock' => $jumlahStock === 0 ? 'Habis' : ($jumlahStock <= 10 ? 'Menipis' : 'Tersedia'),
            ];
        });

        return $this->successResponse($data, 'Berhasil memuat daftar obat dan stok apotek');
    }

    public function getStockObat(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $stocks = StockObat::where('id_apoteker', $apotek->id_apotek)
            ->with('obat')
            ->orderBy('jumlah_stock', 'asc')
            ->get();

        return $this->successResponse($stocks, 'Berhasil memuat data stok obat apotek');
    }

    public function tambahObat(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_obat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric|min:0',
            'kategori' => 'required|string|max:100',
            'tipe_obat' => 'required|in:bebas,bebas_terbatas,keras',
            'dosis' => 'nullable|string|max:255',
            'efek_samping' => 'nullable|string',
            'gambar' => 'nullable|string',
            'jumlah_stock_awal' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi penambahan obat gagal', 422, $validator->errors());
        }

        DB::beginTransaction();
        try {
            $obat = Obat::create([
                'nama_obat' => $request->nama_obat,
                'deskripsi' => $request->deskripsi,
                'harga' => $request->harga,
                'kategori' => $request->kategori,
                'tipe_obat' => $request->tipe_obat,
                'dosis' => $request->dosis,
                'efek_samping' => $request->efek_samping,
                'gambar' => $request->gambar,
            ]);

            $stock = StockObat::create([
                'id_obat' => $obat->id_obat,
                'id_apoteker' => $apotek->id_apotek,
                'jumlah_stock' => $request->jumlah_stock_awal,
            ]);

            DB::commit();

            return $this->successResponse([
                'obat' => $obat,
                'stock' => $stock,
            ], 'Obat baru berhasil ditambahkan ke katalog dan stok apotek', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Gagal menambahkan obat: ' . $e->getMessage(), 500);
        }
    }

    public function updateStock(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'id_obat' => 'required|exists:obat,id_obat',
            'mode' => 'nullable|in:set,tambah',
            'jumlah' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi pembaruan stok gagal', 422, $validator->errors());
        }

        $stock = StockObat::firstOrNew([
            'id_apoteker' => $apotek->id_apotek,
            'id_obat' => $request->id_obat,
        ]);

        $mode = $request->input('mode', 'set');
        if ($mode === 'tambah') {
            $stock->jumlah_stock = max(0, ($stock->jumlah_stock ?? 0) + $request->jumlah);
        } else {
            $stock->jumlah_stock = max(0, $request->jumlah);
        }
        $stock->save();

        return $this->successResponse($stock->fresh('obat'), 'Stok obat berhasil diperbarui');
    }

    // =========================================================================
    // AKTIVITAS 5: PROFILE, JAM OPERASIONAL & AREA LAYANAN
    // =========================================================================

    public function getProfile(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $totalPenjualanSelesai = Pembelian::where('id_apotek', $apotek->id_apotek)
            ->where('status_pesanan', 'selesai')
            ->count();

        $totalStockItem = StockObat::where('id_apoteker', $apotek->id_apotek)->count();

        return $this->successResponse([
            'apotek' => $apotek,
            'statistik' => [
                'total_item_dikelola' => $totalStockItem,
                'total_pesanan_selesai' => $totalPenjualanSelesai,
            ],
        ], 'Berhasil memuat profil apotek');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:apotek,email,{$apotek->id_apotek},id_apotek",
            'no_sip' => 'nullable|string|max:100',
            'jam_operasional' => 'nullable|string|max:100',
            'lokasi_apotek' => 'nullable|string',
            'area_layanan' => 'nullable|string|max:255',
            'status_layanan' => 'nullable|string|in:buka,tutup',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi profil apotek gagal', 422, $validator->errors());
        }

        $apotek->update($validator->validated());

        return $this->successResponse($apotek, 'Profil apotek berhasil diperbarui');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'password_lama' => 'required|string',
            'password_baru' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi kata sandi gagal', 422, $validator->errors());
        }

        if (! Hash::check($request->password_lama, $apotek->password)) {
            return $this->errorResponse('Kata sandi lama tidak cocok', 422);
        }

        $apotek->password = $request->password_baru;
        $apotek->save();

        return $this->successResponse(null, 'Kata sandi apotek berhasil diperbarui');
    }

    /**
     * Get jam operasional apotek.
     */
    public function getJamOperasional(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        return $this->successResponse([
            'id_apotek' => $apotek->id_apotek,
            'nama' => $apotek->nama,
            'jam_operasional' => $apotek->jam_operasional ?? '24 Jam',
            'status_layanan' => $apotek->status_layanan ?? 'buka',
        ], 'Informasi jam operasional apotek');
    }

    /**
     * Update jam operasional apotek.
     */
    public function updateJamOperasional(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'jam_operasional' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi jam operasional gagal', 422, $validator->errors());
        }

        $apotek->update(['jam_operasional' => $request->jam_operasional]);

        return $this->successResponse([
            'id_apotek' => $apotek->id_apotek,
            'jam_operasional' => $apotek->jam_operasional,
        ], 'Jam operasional apotek berhasil diperbarui');
    }

    /**
     * Get area layanan apotek.
     */
    public function getAreaLayanan(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        return $this->successResponse([
            'id_apotek' => $apotek->id_apotek,
            'nama' => $apotek->nama,
            'lokasi_apotek' => $apotek->lokasi_apotek,
            'area_layanan' => $apotek->area_layanan ?? 'Seluruh Wilayah Kota',
        ], 'Informasi area layanan apotek');
    }

    /**
     * Update area layanan apotek.
     */
    public function updateAreaLayanan(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'area_layanan' => 'required|string|max:255',
            'lokasi_apotek' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi area layanan gagal', 422, $validator->errors());
        }

        $apotek->update($validator->validated());

        return $this->successResponse([
            'id_apotek' => $apotek->id_apotek,
            'area_layanan' => $apotek->area_layanan,
            'lokasi_apotek' => $apotek->lokasi_apotek,
        ], 'Area layanan apotek berhasil diperbarui');
    }

    /**
     * Toggle status layanan buka / tutup apotek.
     */
    public function toggleStatusLayanan(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        if ($request->has('status_layanan')) {
            $newStatus = strtolower($request->status_layanan) === 'buka' ? 'buka' : 'tutup';
        } else {
            $newStatus = ($apotek->status_layanan === 'tutup') ? 'buka' : 'tutup';
        }

        $apotek->status_layanan = $newStatus;
        $apotek->save();

        return $this->successResponse([
            'id_apotek' => $apotek->id_apotek,
            'status_layanan' => $apotek->status_layanan,
            'pesan' => "Apotek sekarang dalam status {$apotek->status_layanan}",
        ], "Status operasional apotek berhasil diubah menjadi {$apotek->status_layanan}");
    }

    /**
     * Apotek melihat riwayat aktivitas.
     */
    public function getRiwayatAktivitas(Request $request): JsonResponse
    {
        $apotek = $this->getAuthenticatedApotek($request);
        if (! $apotek) {
            return $this->errorResponse('Data apotek tidak ditemukan', 404);
        }

        $riwayatPesanan = Pembelian::where('id_apotek', $apotek->id_apotek)
            ->with(['pasien:id_pasien,nama', 'obat:id_obat,nama_obat'])
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($p) {
                return [
                    'tipe' => 'pesanan',
                    'judul' => "Pesanan #{$p->id_pembelian} ({$p->status_pesanan})",
                    'deskripsi' => "Pasien: " . ($p->pasien->nama ?? '-') . " - Obat: " . ($p->obat->nama_obat ?? '-') . " ({$p->tipe_pembelian})",
                    'status' => $p->status_pesanan,
                    'waktu' => $p->updated_at,
                ];
            });

        $riwayatResep = ResepObat::where('id_apoteker', $apotek->id_apotek)
            ->with(['pasien:id_pasien,nama', 'obat:id_obat,nama_obat', 'dokter:id_dokter,nama'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($r) {
                return [
                    'tipe' => 'resep',
                    'judul' => "Validasi Resep Dokter #{$r->id_resep}",
                    'deskripsi' => "Dokter: " . ($r->dokter->nama ?? '-') . " untuk Pasien: " . ($r->pasien->nama ?? '-') . " - Obat: " . ($r->obat->nama_obat ?? '-'),
                    'status' => 'disetujui',
                    'waktu' => $r->updated_at,
                ];
            });

        $aktivitas = $riwayatPesanan->concat($riwayatResep)
            ->sortByDesc('waktu')
            ->values()
            ->take(25);

        return $this->successResponse($aktivitas, 'Berhasil memuat riwayat aktivitas apotek');
    }
}
