<?php

namespace Database\Seeders;

use App\Models\Apotek;
use App\Models\ApotekAreaLayanan;
use App\Models\ApotekJamOperasional;
use App\Models\Dokter;
use App\Models\EdukasiArtikel;
use App\Models\Konsultasi;
use App\Models\KonsultasiPembayaran;
use App\Models\KonsultasiPesan;
use App\Models\KonsultasiVideoSession;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\ObatBatch;
use App\Models\PantauKesehatan;
use App\Models\Pasien;
use App\Models\PesananObat;
use App\Models\PesananObatItem;
use App\Models\PesananObatTracking;
use App\Models\PragiChat;
use App\Models\PragiJawabanDetail;
use App\Models\PragiPertanyaan;
use App\Models\PragiSkrining;
use App\Models\Reminder;
use App\Models\Resep;
use App\Models\ResepItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds for GIAT (Ginjal Sehat) according to ERD.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Bersihkan tabel relasional secara aman
        DB::statement('TRUNCATE TABLE 
            pesanan_obat_items,
            pesanan_obat_trackings,
            pesanan_obat,
            obat_batches,
            obat,
            apotek_area_layanan,
            apotek_jam_operasional,
            resep_item,
            resep,
            konsultasi_pesan,
            konsultasi_video_session,
            konsultasi_pembayaran,
            konsultasi,
            reminders,
            pantau_kesehatan,
            pragi_chats,
            pragi_jawaban_detail,
            pragi_skrining,
            pragi_pertanyaan,
            edukasi_artikel,
            notifikasi,
            personal_access_tokens,
            apotek,
            dokter,
            pasien,
            "user"
        CASCADE;');

        // ==========================================
        // 1. SEED PASIEN (5 Pasien Lengkap)
        // ==========================================
        $pasienData = [
            [
                'id' => (string) Str::uuid(),
                'nama' => 'Siti Aisyah',
                'email' => 'siti.aisyah@gmail.com',
                'no_hp' => '081234567891',
                'alamat' => 'Jl. Dago Asri No. 12, Coblong, Kota Bandung',
                'jenis_kelamin' => 'P',
                'nik' => '3273014204980002',
                'tanggal_lahir' => '1998-04-12',
                'golongan_darah' => 'O',
                'foto_profile' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'Rina Wijaya',
                'email' => 'rina.wijaya@gmail.com',
                'no_hp' => '082198765432',
                'alamat' => 'Jl. Setiabudi No. 45, Sukasari, Kota Bandung',
                'jenis_kelamin' => 'P',
                'nik' => '3273016009950001',
                'tanggal_lahir' => '1995-09-20',
                'golongan_darah' => 'A',
                'foto_profile' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'no_hp' => '085712348765',
                'alamat' => 'Jl. Buah Batu No. 88, Lengkong, Kota Bandung',
                'jenis_kelamin' => 'P',
                'nik' => '3273015801000003',
                'tanggal_lahir' => '2000-01-18',
                'golongan_darah' => 'B',
                'foto_profile' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'Budi Prasetyo',
                'email' => 'budi.prasetyo@gmail.com',
                'no_hp' => '081398712345',
                'alamat' => 'Jl. Riau No. 102, Sumur Bandung, Kota Bandung',
                'jenis_kelamin' => 'L',
                'nik' => '3273010511920004',
                'tanggal_lahir' => '1992-11-05',
                'golongan_darah' => 'AB',
                'foto_profile' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'Nurul Hidayah',
                'email' => 'nurul.hidayah@gmail.com',
                'no_hp' => '087812984567',
                'alamat' => 'Jl. Sukajadi No. 210, Sukajadi, Kota Bandung',
                'jenis_kelamin' => 'P',
                'nik' => '3273016807970005',
                'tanggal_lahir' => '1997-07-28',
                'golongan_darah' => 'O',
                'foto_profile' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
            ],
        ];

        $pasienModels = [];
        foreach ($pasienData as $p) {
            $user = User::create([
                'id' => $p['id'],
                'email' => $p['email'],
                'password' => Hash::make('password123'),
                'role' => 'pasien',
                'auth_provider' => 'local',
            ]);

            $pasienModels[] = Pasien::create([
                'id_pasien' => $user->id,
                'nama' => $p['nama'],
                'no_hp' => $p['no_hp'],
                'alamat' => $p['alamat'],
                'jenis_kelamin' => $p['jenis_kelamin'],
                'nik' => $p['nik'],
                'tanggal_lahir' => $p['tanggal_lahir'],
                'golongan_darah' => $p['golongan_darah'],
                'foto_profile' => $p['foto_profile'],
            ]);
        }

        // ==========================================
        // 2. SEED DOKTER (5 Dokter Spesialis & Umum)
        // ==========================================
        $dokterData = [
            [
                'id' => (string) Str::uuid(),
                'nama' => 'dr. Andi Wijaya, Sp.PD-KGH',
                'email' => 'dr.andi.wijaya@giat.id',
                'spesialisasi' => 'Dokter Ginjal & Hipertensi',
                'institusi' => 'RSUP Dr. Hasan Sadikin Bandung',
                'no_str' => 'STR-3271-2015-88912',
                'no_sip' => 'SIP-440/123/Dinkes-Bdg/2021',
                'no_hp' => '081223344551',
                'tarif_konsultasi' => 150000,
                'foto_profile' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=300&q=80',
                'bio' => 'Konsultan Ginjal dan Hipertensi dengan pengalaman lebih dari 12 tahun menangani gagal ginjal kronis, dialisis, dan penyakit glomerulus.',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'dr. Budi Santoso, Sp.PD',
                'email' => 'dr.budi.santoso@giat.id',
                'spesialisasi' => 'Dokter Spesialis Penyakit Dalam',
                'institusi' => 'RS Santo Borromeus Bandung',
                'no_str' => 'STR-3271-2018-99411',
                'no_sip' => 'SIP-440/456/Dinkes-Bdg/2022',
                'no_hp' => '081334455662',
                'tarif_konsultasi' => 100000,
                'foto_profile' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=300&q=80',
                'bio' => 'Spesialis penyakit dalam berfokus pada pencegahan komplikasi metabolik seperti diabetes dan hipertensi terhadap fungsi ginjal.',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'dr. Citra Dewi, Sp.GK',
                'email' => 'dr.citra.dewi@giat.id',
                'spesialisasi' => 'Dokter Gizi Klinis - Diet Ginjal',
                'institusi' => 'RS Advent Bandung',
                'no_str' => 'STR-3271-2019-77123',
                'no_sip' => 'SIP-440/789/Dinkes-Bdg/2023',
                'no_hp' => '081445566773',
                'tarif_konsultasi' => 125000,
                'foto_profile' => 'https://images.unsplash.com/photo-1594824813501-4467793d5df2?auto=format&fit=crop&w=300&q=80',
                'bio' => 'Dokter spesialis gizi klinis yang mendampingi perencanaan nutrisi rendah protein, rendah natrium, dan rendah fosfat untuk pasien ginjal.',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'dr. Dedi Pratama',
                'email' => 'dr.dedi.pratama@giat.id',
                'spesialisasi' => 'Dokter Umum',
                'institusi' => 'Klinik Utama Sehat Ginjal',
                'no_str' => 'STR-3271-2021-33214',
                'no_sip' => 'SIP-440/321/Dinkes-Bdg/2024',
                'no_hp' => '081556677884',
                'tarif_konsultasi' => 50000,
                'foto_profile' => 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?auto=format&fit=crop&w=300&q=80',
                'bio' => 'Dokter umum berdedikasi untuk skrining primer deteksi dini penyakit ginjal kronik dan edukasi pencegahan di komunitas.',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama' => 'dr. Eka Rahmawati, Sp.PD',
                'email' => 'dr.eka.rahmawati@giat.id',
                'spesialisasi' => 'Dokter Spesialis Penyakit Dalam',
                'institusi' => 'RS Hermina Pasteur Bandung',
                'no_str' => 'STR-3271-2020-55112',
                'no_sip' => 'SIP-440/654/Dinkes-Bdg/2023',
                'no_hp' => '081667788995',
                'tarif_konsultasi' => 110000,
                'foto_profile' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=300&q=80',
                'bio' => 'Spesialis penyakit dalam dengan keahlian tata laksana batu ginjal, infeksi saluran kemih berulang, dan sindrom nefrotik.',
            ],
        ];

        $dokterModels = [];
        foreach ($dokterData as $d) {
            $user = User::create([
                'id' => $d['id'],
                'email' => $d['email'],
                'password' => Hash::make('password123'),
                'role' => 'dokter',
                'auth_provider' => 'local',
            ]);

            $dokterModels[] = Dokter::create([
                'id_dokter' => $user->id,
                'nama' => $d['nama'],
                'spesialisasi' => $d['spesialisasi'],
                'institusi' => $d['institusi'],
                'no_str' => $d['no_str'],
                'no_sip' => $d['no_sip'],
                'no_hp' => $d['no_hp'],
                'tarif_konsultasi' => $d['tarif_konsultasi'],
                'foto_profile' => $d['foto_profile'],
                'bio' => $d['bio'],
            ]);
        }

        // ==========================================
        // 3. SEED APOTEK (3 Apotek Mitra)
        // ==========================================
        $apotekData = [
            [
                'id' => (string) Str::uuid(),
                'nama_apotek' => 'Apotek Kimia Farma Dago',
                'email' => 'apotek.dago@kimiafarma.co.id',
                'penanggung_jawab' => 'apt. Farhan Ramadhan, S.Farm',
                'no_sipa_sia' => 'SIPA-3273/1990/440/2022',
                'no_hp' => '0222501234',
                'alamat' => 'Jl. Ir. H. Juanda No. 125, Dago, Coblong, Kota Bandung',
                'lokasi_lat_long' => '-6.8856,107.6136',
                'status_layanan' => true,
                'foto_profile' => 'https://images.unsplash.com/photo-1586015555751-63bb77f4322a?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama_apotek' => 'Apotek K-24 Buah Batu',
                'email' => 'apotek.buahbatu@k24.co.id',
                'penanggung_jawab' => 'apt. Maya Sartika, S.Farm',
                'no_sipa_sia' => 'SIPA-3273/2010/440/2023',
                'no_hp' => '0227305678',
                'alamat' => 'Jl. Buah Batu No. 240, Turangga, Lengkong, Kota Bandung',
                'lokasi_lat_long' => '-6.9452,107.6289',
                'status_layanan' => true,
                'foto_profile' => 'https://images.unsplash.com/photo-1576602976047-174e57a47881?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'id' => (string) Str::uuid(),
                'nama_apotek' => 'Apotek Sehat Ginjal Sentosa',
                'email' => 'apotek.sentosa@gmail.com',
                'penanggung_jawab' => 'apt. Hendra Gunawan, S.Farm',
                'no_sipa_sia' => 'SIPA-3273/2025/440/2024',
                'no_hp' => '0222039988',
                'alamat' => 'Jl. Sukajadi No. 188, Sukasari, Kota Bandung',
                'lokasi_lat_long' => '-6.8801,107.5954',
                'status_layanan' => true,
                'foto_profile' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=400&q=80',
            ],
        ];

        $apotekModels = [];
        foreach ($apotekData as $a) {
            $user = User::create([
                'id' => $a['id'],
                'email' => $a['email'],
                'password' => Hash::make('password123'),
                'role' => 'apotek',
                'auth_provider' => 'local',
            ]);

            $apotekModels[] = Apotek::create([
                'id_apotek' => $user->id,
                'nama_apotek' => $a['nama_apotek'],
                'penanggung_jawab' => $a['penanggung_jawab'],
                'no_sipa_sia' => $a['no_sipa_sia'],
                'no_hp' => $a['no_hp'],
                'alamat' => $a['alamat'],
                'lokasi_lat_long' => $a['lokasi_lat_long'],
                'status_layanan' => $a['status_layanan'],
                'foto_profile' => $a['foto_profile'],
            ]);
        }

        // ==========================================
        // 4. SEED APOTEK JAM OPERASIONAL & AREA LAYANAN
        // ==========================================
        $hariList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        foreach ($apotekModels as $apt) {
            foreach ($hariList as $hari) {
                ApotekJamOperasional::create([
                    'id' => (string) Str::uuid(),
                    'id_apotek' => $apt->id_apotek,
                    'hari' => $hari,
                    'is_open' => true,
                    'jam_buka' => '08:00:00',
                    'jam_tutup' => '22:00:00',
                ]);
            }

            // Area Layanan
            ApotekAreaLayanan::create([
                'id' => (string) Str::uuid(),
                'id_apotek' => $apt->id_apotek,
                'nama_area' => 'Kota Bandung dan Sekitarnya',
                'detail' => 'Layanan pengantaran obat sameday radius 15km',
                'is_active' => true,
            ]);
        }

        // ==========================================
        // 5. SEED OBAT & OBAT_BATCHES
        // ==========================================
        $obatCatalog = [
            [
                'nama_obat' => 'Ketosteril Tab',
                'kategori' => 'Obat Ginjal',
                'bentuk_sediaan' => 'Kaplet Salut Selaput',
                'dosis' => '600 mg',
                'satuan_kemasan' => 'Box (100 Kaplet)',
                'stok_total' => 250,
                'harga_beli' => 240000,
                'harga_jual' => 275000,
                'aturan_pakai_umum' => '1 kaplet per 5 kg berat badan per hari, diminum bersama makanan',
                'foto_obat' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama_obat' => 'Renalvit Tab',
                'kategori' => 'Vitamin & Suplemen',
                'bentuk_sediaan' => 'Tablet Salut',
                'dosis' => 'Komposisi Khusus Ginjal',
                'satuan_kemasan' => 'Botol (30 Tablet)',
                'stok_total' => 180,
                'harga_beli' => 70000,
                'harga_jual' => 85000,
                'aturan_pakai_umum' => '1 tablet sekali sehari setelah makan pagi',
                'foto_obat' => 'https://images.unsplash.com/photo-1550572017-ed21774e5088?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama_obat' => 'Calos 500mg',
                'kategori' => 'Pengikat Fosfat',
                'bentuk_sediaan' => 'Tablet Kunyah',
                'dosis' => '500 mg',
                'satuan_kemasan' => 'Strip (10 Tablet)',
                'stok_total' => 300,
                'harga_beli' => 28000,
                'harga_jual' => 35000,
                'aturan_pakai_umum' => '1 tablet dikunyah bersamaan saat suapan pertama makan',
                'foto_obat' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama_obat' => 'Candesartan 8mg',
                'kategori' => 'Antihipertensi (ARB)',
                'bentuk_sediaan' => 'Tablet',
                'dosis' => '8 mg',
                'satuan_kemasan' => 'Strip (14 Tablet)',
                'stok_total' => 150,
                'harga_beli' => 52000,
                'harga_jual' => 65000,
                'aturan_pakai_umum' => '1 tablet sehari pada waktu yang sama, melindungi fungsi ginjal',
                'foto_obat' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama_obat' => 'Furosemide 40mg',
                'kategori' => 'Diuretik',
                'bentuk_sediaan' => 'Tablet',
                'dosis' => '40 mg',
                'satuan_kemasan' => 'Strip (10 Tablet)',
                'stok_total' => 400,
                'harga_beli' => 9000,
                'harga_jual' => 14000,
                'aturan_pakai_umum' => '1 tablet diminum pagi hari untuk mengurangi retensi cairan/bengkak',
                'foto_obat' => 'https://images.unsplash.com/photo-1550572017-ed21774e5088?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama_obat' => 'Hemobion Kapsul',
                'kategori' => 'Suplemen Anemia',
                'bentuk_sediaan' => 'Kapsul',
                'dosis' => 'Kombinasi Fe & Asam Folat',
                'satuan_kemasan' => 'Strip (10 Kapsul)',
                'stok_total' => 220,
                'harga_beli' => 36000,
                'harga_jual' => 45000,
                'aturan_pakai_umum' => '1 kapsul sehari sesudah makan untuk mengatasi anemia ginjal',
                'foto_obat' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=300&q=80',
            ],
        ];

        $obatModels = [];
        $batchCounter = 100;
        foreach ($apotekModels as $apt) {
            foreach ($obatCatalog as $item) {
                $obatId = (string) Str::uuid();
                $obat = Obat::create([
                    'id_obat' => $obatId,
                    'id_apotek' => $apt->id_apotek,
                    'nama_obat' => $item['nama_obat'],
                    'kategori' => $item['kategori'],
                    'bentuk_sediaan' => $item['bentuk_sediaan'],
                    'dosis' => $item['dosis'],
                    'satuan_kemasan' => $item['satuan_kemasan'],
                    'stok_total' => $item['stok_total'],
                    'harga_beli' => $item['harga_beli'],
                    'harga_jual' => $item['harga_jual'],
                    'aturan_pakai_umum' => $item['aturan_pakai_umum'],
                    'foto_obat' => $item['foto_obat'],
                ]);
                $obatModels[] = $obat;

                // Buat Batch Obat
                $batchCounter++;
                ObatBatch::create([
                    'id' => (string) Str::uuid(),
                    'id_obat' => $obat->id_obat,
                    'id_apotek' => $apt->id_apotek,
                    'no_batch' => 'BATCH-2026-' . $batchCounter,
                    'stok_batch' => $item['stok_total'],
                    'tanggal_kadaluwarsa' => Carbon::now()->addMonths(18)->toDateString(),
                    'status' => 'tersedia',
                ]);
            }
        }

        // ==========================================
        // 6. SEED EDUKASI ARTIKEL (8 Artikel Ginjal)
        // ==========================================
        $artikelData = [
            [
                'judul' => 'Mengenal Stadium Penyakit Ginjal Kronis (CKD) dan Cara Memperlambatnya',
                'kategori' => 'Edukasi Medis',
                'konten' => 'Penyakit Ginjal Kronis (PGK/CKD) terbagi menjadi lima stadium berdasarkan nilai Laju Filtrasi Glomerulus (eGFR). Mengetahui stadium Anda sejak dini sangat penting untuk memperlambat laju keparahan dengan kontrol tekanan darah dan diet seimbang.',
                'gambar_url' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                'penulis' => 'dr. Andi Wijaya, Sp.PD-KGH',
            ],
            [
                'judul' => 'Panduan Diet Rendah Garam, Rendah Kalium & Rendah Fosfat',
                'kategori' => 'Nutrisi & Pola Makan',
                'konten' => 'Ginjal yang menurun fungsinya kesulitan membuang kelebihan natrium, kalium, dan fosfat. Batasi asupan garam maksimal 1 sendok teh per hari, hindari makanan berpengawet kalengan, serta konsultasikan menu dengan ahli gizi klinis.',
                'gambar_url' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=600&q=80',
                'penulis' => 'dr. Citra Dewi, Sp.GK',
            ],
            [
                'judul' => 'Berapa Kebutuhan Cairan Ideal bagi Pasien Ginjal?',
                'kategori' => 'Gaya Hidup',
                'konten' => 'Aturan minum 2 liter sehari tidak berlaku sama bagi penderita gagal ginjal. Pada pasien dengan pembengkakan cairan atau sedang hemodialisis, takaran air dihitung dari jumlah urine 24 jam ditambah 500 ml.',
                'gambar_url' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=600&q=80',
                'penulis' => 'dr. Budi Santoso, Sp.PD',
            ],
            [
                'judul' => 'Peringatan Bahaya Penggunaan Obat Anti-Nyeri Sembarangan (NSAID)',
                'kategori' => 'Keamanan Obat',
                'konten' => 'Obat pereda nyeri golongan NSAID seperti ibuprofen dan asam mefenamat dapat menurunkan aliran darah ke ginjal secara drastis jika dikonsumsi berulang tanpa supervisi dokter.',
                'gambar_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&w=600&q=80',
                'penulis' => 'apt. Farhan Ramadhan, S.Farm',
            ],
        ];

        foreach ($artikelData as $art) {
            EdukasiArtikel::create([
                'id' => (string) Str::uuid(),
                'judul' => $art['judul'],
                'kategori' => $art['kategori'],
                'konten' => $art['konten'],
                'gambar_url' => $art['gambar_url'],
                'penulis' => $art['penulis'],
            ]);
        }

        // ==========================================
        // 7. SEED PRAGI PERTANYAAN
        // ==========================================
        $pertanyaanList = [
            ['pertanyaan' => 'Apakah Anda memiliki riwayat tekanan darah tinggi (hipertensi)?', 'kategori' => 'Riwayat Medis', 'urutan' => 1],
            ['pertanyaan' => 'Apakah Anda memiliki riwayat diabetes melitus atau kadar gula darah tinggi?', 'kategori' => 'Riwayat Medis', 'urutan' => 2],
            ['pertanyaan' => 'Apakah ada anggota keluarga kandung yang memiliki riwayat penyakit ginjal?', 'kategori' => 'Genetika', 'urutan' => 3],
            ['pertanyaan' => 'Apakah Anda sering mengalami pembengkakan pada kelopak mata, pergelangan kaki, atau tungkai?', 'kategori' => 'Gejala Fisik', 'urutan' => 4],
            ['pertanyaan' => 'Apakah urine Anda sering tampak berbusa tebal, keruh, atau berwarna kemerahan?', 'kategori' => 'Urinasi', 'urutan' => 5],
            ['pertanyaan' => 'Apakah Anda sering terbangun di malam hari lebih dari 2 kali untuk buang air kecil?', 'kategori' => 'Urinasi', 'urutan' => 6],
            ['pertanyaan' => 'Apakah Anda rutin mengonsumsi jamu pegal linu atau obat pereda nyeri tanpa resep dokter?', 'kategori' => 'Gaya Hidup & Obat', 'urutan' => 7],
            ['pertanyaan' => 'Apakah Anda sering merasa cepat lelah, lemas, atau sesak napas saat aktivitas ringan?', 'kategori' => 'Gejala Fisik', 'urutan' => 8],
        ];

        $pertanyaanModels = [];
        foreach ($pertanyaanList as $pt) {
            $pertanyaanModels[] = PragiPertanyaan::create([
                'id' => (string) Str::uuid(),
                'pertanyaan' => $pt['pertanyaan'],
                'kategori' => $pt['kategori'],
                'urutan' => $pt['urutan'],
                'is_active' => true,
            ]);
        }

        // ==========================================
        // 8. SEED PRAGI SKRINING, JAWABAN & CHATS
        // ==========================================
        $skriningPasien1 = PragiSkrining::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'total_skor' => 20,
            'kategori_risiko' => 'rendah',
            'rekomendasi' => 'Risiko CKD Anda rendah. Pertahankan pola hidup sehat, cukupi air putih 2 liter/hari, dan periksa tekanan darah berkala.',
            'tanggal_skrining' => $now->subDays(2),
        ]);

        foreach ($pertanyaanModels as $idx => $pt) {
            PragiJawabanDetail::create([
                'id' => (string) Str::uuid(),
                'id_skrining' => $skriningPasien1->id,
                'id_pertanyaan' => $pt->id,
                'jawaban' => ($idx < 2) ? 'Ya' : 'Tidak',
            ]);
        }

        // AI PRAGI Chat logs
        PragiChat::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'role_sender' => 'user',
            'pesan' => 'Halo Pragi, apa tanda-tanda awal ginjal mulai bermasalah?',
            'created_at' => $now->subHours(5),
        ]);
        PragiChat::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'role_sender' => 'assistant',
            'pesan' => 'Halo Siti! Gejala awal penurunan fungsi ginjal antara lain urine berbusa, pembengkakan kaki/kelopak mata di pagi hari, mudah lelah, dan tekanan darah yang mendadak sulit terkontrol.',
            'created_at' => $now->subHours(5)->addMinutes(1),
        ]);

        // ==========================================
        // 9. SEED PANTAU KESEHATAN (Pasien 1)
        // ==========================================
        PantauKesehatan::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'berat_badan' => 54.0,
            'tinggi_badan' => 160.0,
            'bmi' => 21.09,
            'kategori_bmi' => 'normal',
            'kondisi' => 'baik',
            'keluhan' => 'Tidak ada keluhan berarti',
            'detail_keluhan' => 'Kondisi stabil, urin jernih dan tekanan darah normal 115/75 mmHg.',
            'tanggal_pantau' => $now->subDay(),
        ]);

        // ==========================================
        // 10. SEED REMINDERS
        // ==========================================
        Reminder::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'tipe' => 'obat',
            'judul' => 'Minum Renalvit Pagi',
            'subjudul' => '1 tablet setelah sarapan pagi',
            'waktu' => '07:30:00',
            'tanggal' => $now->toDateString(),
            'pengulangan' => 'harian',
            'status' => 'aktif',
            'is_active' => true,
        ]);
        Reminder::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'tipe' => 'minum_air',
            'judul' => 'Jadwal Minum Air Putih',
            'subjudul' => 'Gelas ke-3 (250 ml)',
            'waktu' => '11:00:00',
            'tanggal' => $now->toDateString(),
            'pengulangan' => 'harian',
            'status' => 'aktif',
            'is_active' => true,
        ]);

        // ==========================================
        // 11. SEED KONSULTASI, PEMBAYARAN, SESI VIDEO & PESAN
        // ==========================================
        $konsultasi = Konsultasi::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'id_dokter' => $dokterModels[0]->id_dokter,
            'tanggal_konsultasi' => $now->toDateString(),
            'jam_mulai' => '14:00:00',
            'jam_selesai' => '14:30:00',
            'keluhan_awal' => 'Pinggang belakang agak pegal sejak 3 hari lalu dan urine sedikit berbusa.',
            'jenis_layanan' => 'chat',
            'status' => 'berlangsung',
        ]);

        KonsultasiPembayaran::create([
            'id' => (string) Str::uuid(),
            'id_konsultasi' => $konsultasi->id,
            'no_invoice' => 'INV-GIAT-' . strtoupper(Str::random(8)),
            'jumlah_bayar' => 150000,
            'metode_pembayaran' => 'va',
            'va_number' => '8808123456789012',
            'status_bayar' => 'lunas',
            'waktu_bayar' => $now->subMinutes(30),
        ]);

        KonsultasiVideoSession::create([
            'id' => (string) Str::uuid(),
            'id_konsultasi' => $konsultasi->id,
            'room_id' => 'room_' . Str::random(12),
            'token' => 'livekit_jwt_token_sample_abc123',
            'durasi_menit' => 0,
            'status_panggilan' => 'menunggu',
        ]);

        KonsultasiPesan::create([
            'id' => (string) Str::uuid(),
            'id_konsultasi' => $konsultasi->id,
            'id_sender' => $pasienModels[0]->id_pasien,
            'pesan' => 'Selamat siang dokter, ini keluhan saya pinggang pegal dan urine agak berbusa.',
            'created_at' => $now->subMinutes(25),
        ]);
        KonsultasiPesan::create([
            'id' => (string) Str::uuid(),
            'id_konsultasi' => $konsultasi->id,
            'id_sender' => $dokterModels[0]->id_dokter,
            'pesan' => 'Selamat siang Bu Siti. Apakah ada riwayat demam atau anyang-anyangan saat buang air kecil?',
            'created_at' => $now->subMinutes(23),
        ]);

        // ==========================================
        // 12. SEED RESEP & RESEP_ITEM
        // ==========================================
        $resep = Resep::create([
            'id' => (string) Str::uuid(),
            'no_resep' => 'RXP-' . date('Ymd') . '-001',
            'id_konsultasi' => $konsultasi->id,
            'id_dokter' => $dokterModels[0]->id_dokter,
            'id_pasien' => $pasienModels[0]->id_pasien,
            'tanggal_resep' => $now->toDateString(),
            'diagnosis' => 'Suspect Early CKD Stage 1 / ISK Mild',
            'catatan_dokter' => 'Habiskan suplemen dan hindari konsumsi obat anti-nyeri bebas.',
            'status' => 'aktif',
        ]);

        ResepItem::create([
            'id' => (string) Str::uuid(),
            'id_resep' => $resep->id,
            'nama_obat' => 'Renalvit Tab',
            'dosis' => '1x1',
            'aturan_pakai' => 'Diminum sesudah makan',
            'waktu_penggunaan' => 'Pagi hari',
            'jumlah' => 30,
            'catatan_khusus' => 'Suplemen asam amino & multivitamin ginjal',
        ]);

        // ==========================================
        // 13. SEED PESANAN OBAT, ITEM & TRACKING
        // ==========================================
        $pesanan = PesananObat::create([
            'id' => (string) Str::uuid(),
            'no_pesanan' => 'ORD-GIAT-' . strtoupper(Str::random(10)),
            'id_pasien' => $pasienModels[0]->id_pasien,
            'id_apotek' => $apotekModels[0]->id_apotek,
            'id_resep' => $resep->id,
            'tipe_pesanan' => 'resep',
            'metode_pengambilan' => 'diantar',
            'nama_penerima' => 'Siti Aisyah',
            'no_hp_penerima' => '081234567891',
            'alamat_pengiriman' => 'Jl. Dago Asri No. 12, Coblong, Kota Bandung',
            'subtotal' => 85000,
            'ongkos_kirim' => 15000,
            'total_bayar' => 100000,
            'metode_pembayaran' => 'transfer_bank',
            'status_pembayaran' => 'lunas',
            'progress_step' => 2,
            'status_pesanan' => 'diproses',
            'created_at' => $now->subMinutes(15),
            'updated_at' => $now,
        ]);

        PesananObatItem::create([
            'id' => (string) Str::uuid(),
            'id_pesanan' => $pesanan->id,
            'id_obat' => $obatModels[1]->id_obat, // Renalvit
            'nama_obat' => 'Renalvit Tab',
            'jumlah' => 1,
            'harga_satuan' => 85000,
            'subtotal' => 85000,
        ]);

        PesananObatTracking::create([
            'id' => (string) Str::uuid(),
            'id_pesanan' => $pesanan->id,
            'status' => 'Pembayaran Dikonfirmasi',
            'keterangan' => 'Pembayaran pesanan telah diverifikasi oleh sistem GIAT.',
            'created_at' => $now->subMinutes(15),
        ]);
        PesananObatTracking::create([
            'id' => (string) Str::uuid(),
            'id_pesanan' => $pesanan->id,
            'status' => 'Sedang Diproses Apotek',
            'keterangan' => 'Apoteker sedang menyiapkan dan mengemas obat Anda.',
            'created_at' => $now->subMinutes(10),
        ]);

        // ==========================================
        // 14. SEED NOTIFIKASI
        // ==========================================
        Notifikasi::create([
            'id' => (string) Str::uuid(),
            'id_user' => $pasienModels[0]->id_pasien,
            'judul' => 'Konsultasi Dimulai',
            'pesan' => 'Sesi konsultasi Anda bersama dr. Andi Wijaya telah aktif. Silakan masuk ke ruang konsultasi.',
            'kategori' => 'konsultasi',
            'is_read' => false,
            'created_at' => $now->subMinutes(25),
        ]);
        Notifikasi::create([
            'id' => (string) Str::uuid(),
            'id_user' => $pasienModels[0]->id_pasien,
            'judul' => 'Pesanan Obat Sedang Diproses',
            'pesan' => 'Pesanan nomor ' . $pesanan->no_pesanan . ' sedang disiapkan oleh Apotek Kimia Farma Dago.',
            'kategori' => 'obat',
            'is_read' => false,
            'created_at' => $now->subMinutes(10),
        ]);
        Notifikasi::create([
            'id' => (string) Str::uuid(),
            'id_user' => $dokterModels[0]->id_dokter,
            'judul' => 'Pasien Baru Masuk',
            'pesan' => 'Pasien Siti Aisyah telah memasuki ruang konsultasi.',
            'kategori' => 'konsultasi',
            'is_read' => false,
            'created_at' => $now->subMinutes(25),
        ]);
    }
}
