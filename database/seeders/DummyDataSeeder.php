<?php

namespace Database\Seeders;

use App\Models\Apotek;
use App\Models\Dokter;
use App\Models\EdukasiKesehatan;
use App\Models\Konsultasi;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\Pantau;
use App\Models\Pasien;
use App\Models\Pembelian;
use App\Models\Pragi;
use App\Models\Reminder;
use App\Models\ResepObat;
use App\Models\StockObat;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds for GIAT (Ginjal Sehat).
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Bersihkan tabel relasional agar fresh dan konsisten
        DB::statement('TRUNCATE TABLE notifikasi, personal_access_tokens, pembelian, stock_obat, resep_obat, konsultasi_pesan, konsultasi, pantau, reminder, pragi, edukasi_kesehatan, obat, dokter, apotek, pasien CASCADE;');

        // ==========================================
        // 1. SEED PASIEN (5 Pasien dengan Data Lengkap)
        // ==========================================
        $pasienList = [
            [
                'nama' => 'Siti Aisyah',
                'email' => 'siti.aisyah@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '081234567891',
                'alamat' => 'Jl. Dago Asri No. 12, Coblong, Kota Bandung',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '1998-04-12',
                'gol_darah' => 'O',
                'NIK' => '3273014204980002',
                'foto_profile' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama' => 'Rina Wijaya',
                'email' => 'rina.wijaya@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '082198765432',
                'alamat' => 'Jl. Setiabudi No. 45, Sukasari, Kota Bandung',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '1995-09-20',
                'gol_darah' => 'A',
                'NIK' => '3273016009950001',
                'foto_profile' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '085712348765',
                'alamat' => 'Jl. Buah Batu No. 88, Lengkong, Kota Bandung',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2000-01-18',
                'gol_darah' => 'B',
                'NIK' => '3273015801000003',
                'foto_profile' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama' => 'Budi Prasetyo',
                'email' => 'budi.prasetyo@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '081398712345',
                'alamat' => 'Jl. Riau No. 102, Sumur Bandung, Kota Bandung',
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '1992-11-05',
                'gol_darah' => 'AB',
                'NIK' => '3273010511920004',
                'foto_profile' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80',
            ],
            [
                'nama' => 'Nurul Hidayah',
                'email' => 'nurul.hidayah@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '087812984567',
                'alamat' => 'Jl. Sukajadi No. 210, Sukajadi, Kota Bandung',
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '1997-07-28',
                'gol_darah' => 'O',
                'NIK' => '3273016807970005',
                'foto_profile' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
            ],
        ];

        $pasiens = [];
        foreach ($pasienList as $p) {
            $pasiens[] = Pasien::create($p);
        }

        // ==========================================
        // 2. SEED DOKTER (Hanya 3 Kategori Resmi)
        // ==========================================
        $dokterList = [
            [
                'nama' => 'dr. Ahmad Pratama',
                'email' => 'dr.ahmad@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '081122334455',
                'no_sip' => '503/SIP-DU/0124/DINKES/2023',
                'no_str' => '31.1.1.100.2.19.123456',
                'spesialisasi' => 'Dokter Umum',
                'institusi' => 'Klinik Pratama Sehat Bersama',
                'jenis_kelamin' => 'L',
                'alamat_praktik' => 'Jl. Pasteur No. 38, Sukajadi, Bandung',
                'foto_profil' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=300&q=80',
                'biaya_konsultasi' => 35000,
                'notifikasi_settings' => [
                    'notifikasi_chat_pasien' => true,
                    'notifikasi_jadwal_baru' => true,
                    'notifikasi_pengingat_konsultasi' => true,
                    'notifikasi_email' => false,
                ],
            ],
            [
                'nama' => 'dr. Siti Rahmawati, Sp.PD',
                'email' => 'dr.siti@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '081199887766',
                'no_sip' => '503/SIP-DS/0589/DINKES/2024',
                'no_str' => '31.2.1.200.3.20.654321',
                'spesialisasi' => 'Dokter Spesialis Penyakit Dalam',
                'institusi' => 'RSUP Dr. Hasan Sadikin Bandung',
                'jenis_kelamin' => 'P',
                'alamat_praktik' => 'Poli Penyakit Dalam RSHS, Jl. Pasteur No. 38, Bandung',
                'foto_profil' => 'https://images.unsplash.com/photo-1594824813576-23cf0a955740?auto=format&fit=crop&w=300&q=80',
                'biaya_konsultasi' => 75000,
                'notifikasi_settings' => [
                    'notifikasi_chat_pasien' => true,
                    'notifikasi_jadwal_baru' => true,
                    'notifikasi_pengingat_konsultasi' => true,
                    'notifikasi_email' => true,
                ],
            ],
            [
                'nama' => 'dr. Budi Santoso, Sp.PD-KGH',
                'email' => 'dr.budi@gmail.com',
                'password' => Hash::make('password123'),
                'no_hp' => '081233445566',
                'no_sip' => '503/SIP-DS/0931/DINKES/2022',
                'no_str' => '31.1.1.300.1.18.789123',
                'spesialisasi' => 'Dokter Ginjal',
                'institusi' => 'Pusat Ginjal Terpadu RS Al-Ihsan',
                'jenis_kelamin' => 'L',
                'alamat_praktik' => 'Unit Nefrologi & Hemodialisis, Baleendah, Bandung',
                'foto_profil' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?auto=format&fit=crop&w=300&q=80',
                'biaya_konsultasi' => 120000,
                'notifikasi_settings' => [
                    'notifikasi_chat_pasien' => true,
                    'notifikasi_jadwal_baru' => true,
                    'notifikasi_pengingat_konsultasi' => true,
                    'notifikasi_email' => true,
                ],
            ],
        ];

        $dokters = [];
        foreach ($dokterList as $d) {
            $dokters[] = Dokter::create($d);
        }

        // Dummy Perangkat Login Dokter (Sanctum Tokens) untuk testing menu manajemen perangkat
        DB::table('personal_access_tokens')->insert([
            [
                'tokenable_type' => Dokter::class,
                'tokenable_id' => $dokters[0]->id_dokter,
                'name' => 'Chrome on Windows 11 (PC Ruang Praktik)',
                'token' => hash('sha256', 'token-dummy-1'),
                'abilities' => json_encode(['*']),
                'last_used_at' => $now->copy()->subMinutes(15),
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subMinutes(15),
            ],
            [
                'tokenable_type' => Dokter::class,
                'tokenable_id' => $dokters[0]->id_dokter,
                'name' => 'GIAT Doctor Mobile App (iPhone 15 Pro)',
                'token' => hash('sha256', 'token-dummy-2'),
                'abilities' => json_encode(['*']),
                'last_used_at' => $now->copy()->subHours(2),
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subHours(2),
            ],
            [
                'tokenable_type' => Dokter::class,
                'tokenable_id' => $dokters[2]->id_dokter,
                'name' => 'Safari on iPad Pro (Poli Ginjal)',
                'token' => hash('sha256', 'token-dummy-3'),
                'abilities' => json_encode(['*']),
                'last_used_at' => $now->copy()->subMinutes(5),
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subMinutes(5),
            ],
        ]);

        // ==========================================
        // 3. SEED APOTEK
        // ==========================================
        $apotekList = [
            [
                'nama' => 'Apotek Kimia Farma Dago',
                'email' => 'kf.dago@gmail.com',
                'password' => Hash::make('password123'),
                'no_sip' => '445/SIA/0012/DPMPTSP/2021',
                'jam_operasional' => '24 Jam',
                'lokasi_apotek' => 'Jl. Ir. H. Juanda No. 69, Dago, Coblong, Kota Bandung',
                'area_layanan' => 'Bandung Utara, Coblong, Sukasari',
                'status_layanan' => 'buka',
            ],
            [
                'nama' => 'Apotek K-24 Buah Batu',
                'email' => 'k24.buahbatu@gmail.com',
                'password' => Hash::make('password123'),
                'no_sip' => '445/SIA/0098/DPMPTSP/2022',
                'jam_operasional' => '24 Jam',
                'lokasi_apotek' => 'Jl. Buah Batu No. 165, Turangga, Lengkong, Kota Bandung',
                'area_layanan' => 'Bandung Selatan, Buah Batu, Lengkong',
                'status_layanan' => 'buka',
            ],
            [
                'nama' => 'Apotek Mandiri Medika Pasteur',
                'email' => 'mandiri.medika@gmail.com',
                'password' => Hash::make('password123'),
                'no_sip' => '445/SIA/0211/DPMPTSP/2023',
                'jam_operasional' => '07:00 - 22:00',
                'lokasi_apotek' => 'Jl. Dr. Djunjunan No. 143, Pasteur, Sukajadi, Kota Bandung',
                'area_layanan' => 'Bandung Barat, Pasteur, Sukajadi',
                'status_layanan' => 'buka',
            ],
        ];

        $apoteks = [];
        foreach ($apotekList as $a) {
            $apoteks[] = Apotek::create($a);
        }

        // ==========================================
        // 4. SEED OBAT (Khusus Kesehatan Ginjal)
        // ==========================================
        $obatList = [
            [
                'nama_obat' => 'Renalvit Kapsul',
                'harga' => 65000,
                'kategori' => 'Suplemen Ginjal',
                'tipe_obat' => 'bebas',
                'dosis' => '1 kapsul per hari setelah makan pagi',
                'efek_samping' => 'Jarang terjadi, rasa mual ringan pada beberapa orang',
                'deskripsi' => 'Multivitamin larut air khusus pasien penurunan fungsi ginjal untuk menjaga stamina dan regenerasi sel.',
            ],
            [
                'nama_obat' => 'Kalsium Karbonat 500 mg',
                'harga' => 28000,
                'kategori' => 'Pengikat Fosfat',
                'tipe_obat' => 'bebas_terbatas',
                'dosis' => '1-2 tablet dikunyah bersamaan saat makan',
                'efek_samping' => 'Konstipasi ringan atau perut kembung',
                'deskripsi' => 'Mengikat fosfat dari makanan dalam usus agar kadar fosfat darah tetap terjaga dan tidak membebani filtrasi ginjal.',
            ],
            [
                'nama_obat' => 'Paracetamol 500 mg',
                'harga' => 9000,
                'kategori' => 'Pereda Demam & Nyeri',
                'tipe_obat' => 'bebas',
                'dosis' => '1 tablet tiap 6-8 jam bila terasa nyeri/demam',
                'efek_samping' => 'Sangat minimal bila sesuai dosis yang dianjurkan',
                'deskripsi' => 'Pilihan pereda nyeri yang aman untuk ginjal dibanding obat antinyeri golongan NSAID.',
            ],
            [
                'nama_obat' => 'Ketosteril Tablet',
                'harga' => 240000,
                'kategori' => 'Nutrisi Ginjal',
                'tipe_obat' => 'keras',
                'dosis' => 'Sesuai resep dokter (umumnya 4-8 tablet sehari saat makan)',
                'efek_samping' => 'Perlu pemantauan kadar kalsium darah berkala',
                'deskripsi' => 'Asam keto dan asam amino esensial untuk terapi nutrisi pada pasien dengan penurunan laju filtrasi glomerulus.',
            ],
            [
                'nama_obat' => 'Natrium Bikarbonat 500 mg',
                'harga' => 35000,
                'kategori' => 'Penyeimbang Asam Basa',
                'tipe_obat' => 'bebas_terbatas',
                'dosis' => '1 tablet 2 kali sehari setelah makan',
                'efek_samping' => 'Rasa kembung bila berlebihan',
                'deskripsi' => 'Membantu menjaga keseimbangan asam-basa tubuh pada pasien dengan penurunan ekskresi asam ginjal.',
            ],
            [
                'nama_obat' => 'Allopurinol 100 mg',
                'harga' => 22000,
                'kategori' => 'Pengendali Asam Urat',
                'tipe_obat' => 'keras',
                'dosis' => '1 tablet sehari setelah makan',
                'efek_samping' => 'Ruam kulit ringan, rasa kantuk',
                'deskripsi' => 'Mengontrol kadar asam urat darah untuk mencegah kristalisasi dan pembentukan batu ginjal urat.',
            ],
            [
                'nama_obat' => 'Asam Folat 1 mg',
                'harga' => 15000,
                'kategori' => 'Suplemen Darah',
                'tipe_obat' => 'bebas',
                'dosis' => '1 tablet per hari sesudah makan',
                'efek_samping' => 'Tidak ada efek samping bermakna pada dosis standar',
                'deskripsi' => 'Mendukung pembentukan hemoglobin dan sel darah merah.',
            ],
            [
                'nama_obat' => 'Vitamin B Kompleks',
                'harga' => 18000,
                'kategori' => 'Vitamin Saraf & Energi',
                'tipe_obat' => 'bebas',
                'dosis' => '1 tablet sehari sesudah makan',
                'efek_samping' => 'Warna urin kuning cerah (normal)',
                'deskripsi' => 'Mendukung metabolisme energi dan mengurangi keluhan lemas pegal.',
            ],
        ];

        $obats = [];
        foreach ($obatList as $o) {
            $obats[] = Obat::create($o);
        }

        // ==========================================
        // 5. SEED STOCK OBAT (Ada Stok Aman, Menipis, dan Habis)
        // ==========================================
        foreach ($apoteks as $indexApt => $apt) {
            foreach ($obats as $indexObt => $obt) {
                // Buat variasi stok: ada yang menipis (3-8), ada yang cukup (50-120), ada yang habis (0)
                $stockQty = match (true) {
                    $indexObt === 3 => 5, // Ketosteril dibuat menipis (5) untuk notifikasi stock
                    $indexObt === 5 => 0, // Allopurinol di apotek tertentu dibuat 0 (habis)
                    default => rand(40, 150),
                };

                StockObat::create([
                    'id_obat' => $obt->id_obat,
                    'id_apoteker' => $apt->id_apotek,
                    'jumlah_stock' => $stockQty,
                ]);
            }
        }

        // ==========================================
        // 6. SEED EDUKASI KESEHATAN GINJAL
        // ==========================================
        $edukasiList = [
            [
                'judul' => '8 Golden Rules Menjaga Kesehatan Ginjal Tetap Optimal',
                'kategori' => 'Pencegahan Ginjal',
                'isi_edukasi' => "Ginjal adalah organ vital yang menyaring racun dan mengatur cairan tubuh setiap saat.\n\n8 Kunci Menjaga Ginjal Sehat:\n1. Cukupi cairan tubuh dengan minum air putih bersih 2 liter setiap hari.\n2. Batasi konsumsi garam berlebih (maksimal 1 sendok teh per hari).\n3. Jaga pola makan bergizi seimbang dan pertahankan berat badan ideal.\n4. Rutin berolahraga ringan minimal 30 menit sehari.\n5. Hindari merokok dan batasi minuman berpemanis buatan.\n6. Hindari konsumsi obat pereda nyeri (NSAID) berulang tanpa resep dokter.\n7. Pantau kondisi tubuh secara berkala di aplikasi GIAT.\n8. Lakukan skrining berkala jika memiliki riwayat keluarga dengan gangguan ginjal.",
                'gambar' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=600&q=80',
                'tanggal_publish' => $now->copy()->subDays(3),
            ],
            [
                'judul' => 'Mengenal Tanda Awal Penurunan Fungsi Ginjal: Urin Berbusa & Edema',
                'kategori' => 'Edukasi Klinis',
                'isi_edukasi' => "Penurunan fungsi ginjal seringkali terjadi tanpa gejala nyata pada fase awal.\n\nGejala penting yang perlu diwaspadai:\n1. Urin berbusa menetap (tanda kemungkinan adanya kebocoran protein).\n2. Pembengkakan (edema) pada pergelangan kaki atau wajah di pagi hari.\n3. Perubahan volume buang air kecil (frekuensi lebih sering di malam hari).\n4. Rasa lemas berkepanjangan akibat penumpukan sisa metabolisme.\n\nJika Anda merasakan gejala tersebut, segera lakukan skrining di menu PRAGI aplikasi GIAT.",
                'gambar' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=600&q=80',
                'tanggal_publish' => $now->copy()->subDays(6),
            ],
            [
                'judul' => 'Panduan Hidrasi yang Tepat: Berapa Kebutuhan Air Putih Harian Anda?',
                'kategori' => 'Gaya Hidup Sehat',
                'isi_edukasi' => "Ginjal membutuhkan cairan yang cukup untuk melarutkan limbah dan mencegah pembentukan endapan atau batu ginjal.\n\nTips Hidrasi Sehat:\n- Minum 8-10 gelas air putih sehari secara bertahap, bukan langsung banyak dalam satu waktu.\n- Perhatikan warna urin Anda: urin berwarna kuning muda bening menandakan hidrasi baik.\n- Bila berolahraga atau beraktivitas di cuaca terik, tambahkan 1-2 gelas air ekstra.\n- Catat kebiasaan minum harian Anda pada menu Reminder aplikasi GIAT.",
                'gambar' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=80',
                'tanggal_publish' => $now->copy()->subDays(12),
            ],
            [
                'judul' => 'Bahaya Penggunaan Obat Pereda Nyeri (NSAID) Berlebihan bagi Ginjal',
                'kategori' => 'Keamanan Obat',
                'isi_edukasi' => "Obat antinyeri golongan NSAID (seperti asam mefenamat, ibuprofen, dan natrium diklofenak) yang dikonsumsi bebas dalam jangka panjang dapat mengurangi aliran darah ke ginjal.\n\nHal yang perlu diperhatikan:\n- Jangan mengonsumsi antinyeri setiap kali merasa pegal tanpa mencari penyebabnya.\n- Untuk pereda nyeri ringan yang lebih ramah ginjal, parasetamol adalah pilihan yang lebih aman sesuai dosis.\n- Konsultasikan selalu dengan dokter di GIAT sebelum mengonsumsi obat keras secara teratur.",
                'gambar' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=600&q=80',
                'tanggal_publish' => $now->copy()->subDays(18),
            ],
        ];

        $edukasis = [];
        foreach ($edukasiList as $e) {
            $edukasis[] = EdukasiKesehatan::create($e);
        }

        // ==========================================
        // 7. SEED KONSULTASI (Berbagai Status: Selesai Read-Only, Berlangsung, Menunggu Bayar)
        // ==========================================
        // Sesi 1: Selesai (Read-Only)
        $konsul1 = Konsultasi::create([
            'id_pasien' => $pasiens[0]->id_pasien, // Siti Aisyah
            'id_dokter' => $dokters[2]->id_dokter, // dr. Budi Santoso (Dokter Ginjal)
            'tanggal_konsultasi' => $now->copy()->subDays(2),
            'status_konsultasi' => 'selesai',
            'status_pembayaran' => 'lunas',
            'biaya' => 120000,
            'room_id' => 'giat-meet-demo-1',
            'waktu_mulai' => $now->copy()->subDays(2),
            'waktu_selesai' => $now->copy()->subDays(2)->addMinutes(30),
            'isi_konsultasi' => 'Dokter, akhir-akhir ini urin saya terlihat agak berbusa dan pinggang terasa sedikit pegal di sore hari. Apakah ini gejala gangguan ginjal?',
            'catatan_dokter' => 'Berdasarkan diskusi dan riwayat skrining, disarankan untuk memperbanyak konsumsi air putih minimal 2L/hari, hindari obat pereda nyeri, dan konsumsi Renalvit sesuai resep. Pantau kondisi urin selama 1 minggu.',
        ]);

        // Pesan chat sesi 1
        DB::table('konsultasi_pesan')->insert([
            [
                'id_konsultasi' => $konsul1->id_konsultasi,
                'sender_type' => 'pasien',
                'sender_id' => $pasiens[0]->id_pasien,
                'pesan' => 'Selamat siang dokter Budi, hasil tes urin saya ada sedikit busa. Apakah berbahaya?',
                'tipe' => 'text',
                'is_read' => true,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'id_konsultasi' => $konsul1->id_konsultasi,
                'sender_type' => 'dokter',
                'sender_id' => $dokters[2]->id_dokter,
                'pesan' => 'Halo Bu Siti. Urin berbusa bisa menjadi indikasi awal proteinuria (kebocoran protein). Kita lakukan pemeriksaan video call singkat ya untuk evaluasi fisik.',
                'tipe' => 'text',
                'is_read' => true,
                'created_at' => $now->copy()->subDays(2)->addMinutes(5),
                'updated_at' => $now->copy()->subDays(2)->addMinutes(5),
            ],
            [
                'id_konsultasi' => $konsul1->id_konsultasi,
                'sender_type' => 'system',
                'sender_id' => 0,
                'pesan' => 'Sesi konsultasi telah selesai. Riwayat konsultasi ini sekarang bersifat hanya-baca (read-only).',
                'tipe' => 'system',
                'is_read' => true,
                'created_at' => $now->copy()->subDays(2)->addMinutes(30),
                'updated_at' => $now->copy()->subDays(2)->addMinutes(30),
            ],
        ]);

        // Sesi 2: Sedang Berlangsung (WhatsApp Style Chat Aktif & Video Call Siap)
        $konsul2 = Konsultasi::create([
            'id_pasien' => $pasiens[3]->id_pasien, // Budi Prasetyo
            'id_dokter' => $dokters[0]->id_dokter, // dr. Ahmad Pratama (Dokter Umum)
            'tanggal_konsultasi' => $now->copy(),
            'status_konsultasi' => 'berlangsung',
            'status_pembayaran' => 'lunas',
            'biaya' => 35000,
            'room_id' => 'giat-meet-demo-active',
            'waktu_mulai' => $now->copy()->subMinutes(20),
            'isi_konsultasi' => 'Dokter Ahmad, saya sering bekerja di luar ruangan dan jarang minum. Bagaimana cara mengatur jadwal hidrasi yang baik?',
            'catatan_dokter' => null,
        ]);

        DB::table('konsultasi_pesan')->insert([
            [
                'id_konsultasi' => $konsul2->id_konsultasi,
                'sender_type' => 'pasien',
                'sender_id' => $pasiens[3]->id_pasien,
                'pesan' => 'Selamat siang dokter Ahmad, saya sering bekerja di lapangan dan kadang lupa minum air putih.',
                'tipe' => 'text',
                'is_read' => true,
                'created_at' => $now->copy()->subMinutes(18),
                'updated_at' => $now->copy()->subMinutes(18),
            ],
            [
                'id_konsultasi' => $konsul2->id_konsultasi,
                'sender_type' => 'dokter',
                'sender_id' => $dokters[0]->id_dokter,
                'pesan' => 'Selamat siang Pak Budi. Kurang minum saat aktivitas berat dapat membebani kerja ginjal dan memicu batu ginjal. Selalu bawa botol 1L dan atur pengingat di menu Reminder GIAT ya.',
                'tipe' => 'text',
                'is_read' => true,
                'created_at' => $now->copy()->subMinutes(12),
                'updated_at' => $now->copy()->subMinutes(12),
            ],
            [
                'id_konsultasi' => $konsul2->id_konsultasi,
                'sender_type' => 'dokter',
                'sender_id' => $dokters[0]->id_dokter,
                'pesan' => 'Panggilan video call dimulai. Klik tautan untuk bergabung: https://meet.jit.si/giat-meet-demo-active',
                'tipe' => 'video_call',
                'is_read' => false,
                'created_at' => $now->copy()->subMinutes(5),
                'updated_at' => $now->copy()->subMinutes(5),
            ],
        ]);

        // Sesi 3: Mendatang / Jadwal Hari Esok
        Konsultasi::create([
            'id_pasien' => $pasiens[1]->id_pasien, // Rina Wijaya
            'id_dokter' => $dokters[1]->id_dokter, // dr. Siti Rahmawati, Sp.PD
            'tanggal_konsultasi' => $now->copy()->addDay()->setHour(10)->setMinute(0),
            'status_konsultasi' => 'menunggu_pembayaran',
            'status_pembayaran' => 'menunggu_pembayaran',
            'biaya' => 75000,
            'room_id' => null,
            'isi_konsultasi' => 'Konsultasi lanjutan evaluasi hasil skrining PRAGI risiko sedang.',
            'catatan_dokter' => null,
        ]);

        // ==========================================
        // 8. SEED RESEP OBAT
        // ==========================================
        $resep1 = ResepObat::create([
            'id_dokter' => $dokters[2]->id_dokter, // dr. Budi (Dokter Ginjal)
            'id_pasien' => $pasiens[0]->id_pasien, // Siti Aisyah
            'id_obat' => $obats[0]->id_obat, // Renalvit
            'id_apoteker' => $apoteks[0]->id_apotek,
            'dosis' => '1 x 1 kapsul per hari setelah makan pagi',
            'tanggal_resep' => $now->copy()->subDays(2)->toDateString(),
        ]);

        $resep2 = ResepObat::create([
            'id_dokter' => $dokters[1]->id_dokter, // dr. Siti (Sp.PD)
            'id_pasien' => $pasiens[1]->id_pasien, // Rina Wijaya
            'id_obat' => $obats[1]->id_obat, // Kalsium Karbonat
            'id_apoteker' => $apoteks[1]->id_apotek,
            'dosis' => '1 x 1 tablet bersama makanan siang',
            'tanggal_resep' => $now->copy()->subDay()->toDateString(),
        ]);

        $resep3 = ResepObat::create([
            'id_dokter' => $dokters[2]->id_dokter, // dr. Budi (Dokter Ginjal)
            'id_pasien' => $pasiens[2]->id_pasien, // Dewi Lestari
            'id_obat' => $obats[3]->id_obat, // Ketosteril (Obat Keras)
            'id_apoteker' => $apoteks[0]->id_apotek,
            'dosis' => '3 x 2 tablet sehari bersama makan',
            'tanggal_resep' => $now->toDateString(),
        ]);

        // ==========================================
        // 9. SEED PRAGI (Riwayat 3 Skrining Terakhir Pasien)
        // ==========================================
        // Siti Aisyah (3 riwayat skrining)
        Pragi::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'pertanyaan' => json_encode([1 => '0', 2 => '0', 3 => '0', 4 => '0', 5 => '1', 6 => '0', 7 => '0', 8 => '0', 9 => '0', 10 => '0']),
            'hasil_prediksi' => 'Risiko Rendah CKD',
            'rekomendasi' => 'Fungsi ginjal Anda stabil. Terus cukupi hidrasi air putih minimal 2L per hari.',
            'tanggal_screening' => $now->copy()->subDays(14),
        ]);

        Pragi::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'pertanyaan' => json_encode([1 => '1', 2 => '0', 3 => '0', 4 => '1', 5 => '1', 6 => '0', 7 => '0', 8 => '0', 9 => '0', 10 => '0']),
            'hasil_prediksi' => 'Risiko Sedang CKD',
            'rekomendasi' => 'Terdeteksi keluhan pegal pinggang dan urin sedikit berbusa. Disarankan konsultasi dengan Dokter Spesialis Penyakit Dalam atau Dokter Ginjal.',
            'tanggal_screening' => $now->copy()->subDays(7),
        ]);

        Pragi::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'pertanyaan' => json_encode([1 => '0', 2 => '0', 3 => '0', 4 => '0', 5 => '0', 6 => '0', 7 => '0', 8 => '0', 9 => '0', 10 => '0']),
            'hasil_prediksi' => 'Risiko Rendah CKD',
            'rekomendasi' => 'Fungsi ginjal membaik setelah asupan cairan cukup dan minum suplemen teratur.',
            'tanggal_screening' => $now->copy()->subDays(1),
        ]);

        // Rina Wijaya
        Pragi::create([
            'id_pasien' => $pasiens[1]->id_pasien,
            'pertanyaan' => json_encode([1 => '1', 2 => '0', 3 => '1', 4 => '1', 5 => '0', 6 => '0', 7 => '1', 8 => '0', 9 => '0', 10 => '0']),
            'hasil_prediksi' => 'Risiko Sedang CKD',
            'rekomendasi' => 'Terdeteksi beberapa faktor risiko ginjal. Disarankan membatasi makanan asin dan rutin memantau kondisi di menu Pantau.',
            'tanggal_screening' => $now->copy()->subDays(3),
        ]);

        // Budi Prasetyo
        Pragi::create([
            'id_pasien' => $pasiens[3]->id_pasien,
            'pertanyaan' => json_encode([1 => '0', 2 => '0', 3 => '0', 4 => '0', 5 => '0', 6 => '0', 7 => '0', 8 => '0', 9 => '0', 10 => '0']),
            'hasil_prediksi' => 'Risiko Rendah CKD',
            'rekomendasi' => 'Kondisi kesehatan ginjal prima. Terus jaga pola hidrasi sehat dan olahraga rutin.',
            'tanggal_screening' => $now->copy()->subDay(),
        ]);

        // ==========================================
        // 10. SEED REMINDER (Pengingat Pasien)
        // ==========================================
        $reminder1 = Reminder::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'nama' => 'Minum Renalvit Kapsul',
            'tanggal' => $now->toDateString(),
            'waktu' => '07:30:00',
            'keterangan' => 'Minum 1 kapsul setelah sarapan pagi untuk dukung fungsi ginjal.',
            'pengulangan' => 'Setiap Hari',
            'is_active' => true,
        ]);

        $reminder2 = Reminder::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'nama' => 'Minum Air Putih 2 Gelas (Target 2L/hari)',
            'tanggal' => $now->toDateString(),
            'waktu' => '10:00:00',
            'keterangan' => 'Jaga kecukupan hidrasi ginjal di sela-sela aktivitas.',
            'pengulangan' => 'Setiap Hari',
            'is_active' => true,
        ]);

        $reminder3 = Reminder::create([
            'id_pasien' => $pasiens[1]->id_pasien,
            'nama' => 'Jadwal Kontrol Rutin ke Dokter Ginjal',
            'tanggal' => $now->copy()->addDays(5)->toDateString(),
            'waktu' => '09:00:00',
            'keterangan' => 'Evaluasi mingguan catatan kondisi ginjal di menu Pantau.',
            'pengulangan' => 'Satu Kali',
            'is_active' => true,
        ]);

        // ==========================================
        // 11. SEED PANTAU (Catatan Harian Pasien yang Muncul di Detail Dokter)
        // ==========================================
        Pantau::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'id_dokter' => $dokters[2]->id_dokter,
            'id_reminder' => $reminder1->id_reminder,
            'imt' => 22.85,
            'bb' => 58.5,
            'tb' => 160.0,
            'keluhan' => 'Pinggang agak pegal di sore hari',
            'detail_keluhan' => 'Pegal berkurang setelah minum air hangat dan istirahat.',
            'kondisi' => 'Baik dan Terkontrol',
            'catatan' => 'Urin tampak lebih bening dibanding kemarin. Pola minum 2 liter terjaga.',
            'created_at' => $now->copy()->subDays(2),
            'updated_at' => $now->copy()->subDays(2),
        ]);

        Pantau::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'id_dokter' => $dokters[2]->id_dokter,
            'id_reminder' => null,
            'imt' => 22.66,
            'bb' => 58.0,
            'tb' => 160.0,
            'keluhan' => 'Tidak ada keluhan',
            'detail_keluhan' => 'Tubuh terasa segar, buang air kecil lancar dan tidak berbusa.',
            'kondisi' => 'Sangat Baik',
            'catatan' => 'Tetap pertahankan pola makan rendah garam.',
            'created_at' => $now->copy()->subDay(),
            'updated_at' => $now->copy()->subDay(),
        ]);

        Pantau::create([
            'id_pasien' => $pasiens[1]->id_pasien,
            'id_dokter' => $dokters[1]->id_dokter,
            'id_reminder' => $reminder3->id_reminder,
            'imt' => 24.22,
            'bb' => 62.0,
            'tb' => 160.0,
            'keluhan' => 'Kaki sedikit pegal setelah seharian duduk',
            'detail_keluhan' => 'Tidak tampak pembengkakan nyata, istirahat cukup.',
            'kondisi' => 'Stabil',
            'catatan' => 'Kurangi konsumsi makanan asin dan perbanyak jalan santai.',
            'created_at' => $now->copy()->subDays(3),
            'updated_at' => $now->copy()->subDays(3),
        ]);

        // ==========================================
        // 12. SEED PEMBELIAN & PESANAN (Semua Status untuk Testing Apotek)
        // ==========================================
        // Pesanan 1: Selesai (Resep Dokter)
        Pembelian::create([
            'id_pasien' => $pasiens[0]->id_pasien,
            'id_obat' => $obats[0]->id_obat, // Renalvit
            'id_resep' => $resep1->id_resep,
            'id_apotek' => $apoteks[0]->id_apotek,
            'tipe_pembelian' => 'resep',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'selesai',
            'total_harga' => 65000,
            'jumlah' => 1,
            'alamat_pengiriman' => 'Jl. Dago Asri No. 12, Bandung',
            'catatan' => 'Penebusan resep dokter dr. Budi Santoso',
            'nomor_resi' => 'GIAT-BDG-00101',
            'kurir' => 'GIAT Medika Express',
            'status_lacak' => [
                ['status' => 'Pesanan Dibuat', 'deskripsi' => 'Penebusan resep dibuat oleh pasien.', 'waktu' => $now->copy()->subDays(2)->toIso8601String(), 'selesai' => true],
                ['status' => 'Resep Divalidasi Apoteker', 'deskripsi' => 'Resep divalidasi oleh Kimia Farma Dago.', 'waktu' => $now->copy()->subDays(2)->addMinutes(10)->toIso8601String(), 'selesai' => true],
                ['status' => 'Dalam Pengiriman', 'deskripsi' => 'Kurir membawa paket obat ke alamat.', 'waktu' => $now->copy()->subDays(2)->addHours(1)->toIso8601String(), 'selesai' => true],
                ['status' => 'Pesanan Selesai', 'deskripsi' => 'Obat telah sampai di tangan pasien.', 'waktu' => $now->copy()->subDays(2)->addHours(2)->toIso8601String(), 'selesai' => true],
            ],
            'tanggal_pembelian' => $now->copy()->subDays(2),
        ]);

        // Pesanan 2: Selesai (Obat Bebas)
        Pembelian::create([
            'id_pasien' => $pasiens[3]->id_pasien,
            'id_obat' => $obats[2]->id_obat, // Paracetamol OTC
            'id_resep' => null,
            'id_apotek' => $apoteks[1]->id_apotek,
            'tipe_pembelian' => 'umum',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'selesai',
            'total_harga' => 18000,
            'jumlah' => 2,
            'alamat_pengiriman' => 'Jl. Riau No. 102, Bandung',
            'catatan' => 'Beli obat bebas persediaan kotak P3K',
            'nomor_resi' => 'GIAT-BDG-00102',
            'kurir' => 'GIAT Medika Express',
            'status_lacak' => [
                ['status' => 'Pesanan Dibuat', 'deskripsi' => 'Pesanan obat bebas dibuat.', 'waktu' => $now->copy()->subHours(12)->toIso8601String(), 'selesai' => true],
                ['status' => 'Dikonfirmasi Apotek', 'deskripsi' => 'Pesanan dikemas oleh K-24 Buah Batu.', 'waktu' => $now->copy()->subHours(11)->toIso8601String(), 'selesai' => true],
                ['status' => 'Pesanan Selesai', 'deskripsi' => 'Paket diterima oleh Pak Budi.', 'waktu' => $now->copy()->subHours(10)->toIso8601String(), 'selesai' => true],
            ],
            'tanggal_pembelian' => $now->copy()->subHours(12),
        ]);

        // Pesanan 3: Menunggu Konfirmasi Apotek (Siap dicoba tombol "Terima Pesanan")
        Pembelian::create([
            'id_pasien' => $pasiens[1]->id_pasien,
            'id_obat' => $obats[0]->id_obat, // Renalvit
            'id_resep' => null,
            'id_apotek' => $apoteks[0]->id_apotek, // Kimia Farma
            'tipe_pembelian' => 'umum',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'menunggu_konfirmasi',
            'total_harga' => 65000,
            'jumlah' => 1,
            'alamat_pengiriman' => 'Jl. Setiabudi No. 45, Bandung',
            'catatan' => 'Mohon dipacking rapi dengan bubble wrap ya',
            'nomor_resi' => 'GIAT-BDG-00103',
            'kurir' => 'GIAT Medika Express',
            'status_lacak' => [
                ['status' => 'Pesanan Dibuat', 'deskripsi' => 'Pesanan berhasil dibuat, menunggu konfirmasi apotek.', 'waktu' => $now->copy()->subMinutes(30)->toIso8601String(), 'selesai' => true],
            ],
            'tanggal_pembelian' => $now->copy()->subMinutes(30),
        ]);

        // Pesanan 4: Sedang Diproses Apotek (Siap dicoba tombol "Kirim Pesanan")
        Pembelian::create([
            'id_pasien' => $pasiens[2]->id_pasien,
            'id_obat' => $obats[1]->id_obat, // Kalsium Karbonat
            'id_resep' => null,
            'id_apotek' => $apoteks[0]->id_apotek,
            'tipe_pembelian' => 'umum',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'diproses',
            'total_harga' => 56000,
            'jumlah' => 2,
            'alamat_pengiriman' => 'Jl. Buah Batu No. 88, Bandung',
            'catatan' => 'Obat sedang disiapkan oleh staf apotek',
            'nomor_resi' => 'GIAT-BDG-00104',
            'kurir' => 'GIAT Medika Express',
            'status_lacak' => [
                ['status' => 'Pesanan Dibuat', 'deskripsi' => 'Pesanan berhasil dibuat.', 'waktu' => $now->copy()->subHours(2)->toIso8601String(), 'selesai' => true],
                ['status' => 'Dikonfirmasi Apotek', 'deskripsi' => 'Apoteker sedang menyiapkan obat.', 'waktu' => $now->copy()->subHours(1)->toIso8601String(), 'selesai' => true],
            ],
            'tanggal_pembelian' => $now->copy()->subHours(2),
        ]);

        // Pesanan 5: Resep Dokter Masuk (Siap dicoba tombol "Validasi Resep Apoteker")
        Pembelian::create([
            'id_pasien' => $pasiens[2]->id_pasien,
            'id_obat' => $obats[3]->id_obat, // Ketosteril
            'id_resep' => $resep3->id_resep,
            'id_apotek' => $apoteks[0]->id_apotek,
            'tipe_pembelian' => 'resep',
            'status_pembayaran' => 'lunas',
            'status_pesanan' => 'menunggu_konfirmasi',
            'total_harga' => 240000,
            'jumlah' => 1,
            'alamat_pengiriman' => 'Jl. Buah Batu No. 88, Bandung',
            'catatan' => 'Penebusan resep Ketosteril dari dokter ginjal',
            'nomor_resi' => 'GIAT-BDG-00105',
            'kurir' => 'GIAT Medika Express',
            'status_lacak' => [
                ['status' => 'Pengajuan Resep Dibuat', 'deskripsi' => 'Menunggu verifikasi resep oleh apoteker.', 'waktu' => $now->copy()->subMinutes(15)->toIso8601String(), 'selesai' => true],
            ],
            'tanggal_pembelian' => $now->copy()->subMinutes(15),
        ]);

        // ==========================================
        // 13. SEED NOTIFIKASI (Pasien, Dokter, Apotek)
        // ==========================================
        $notifikasiList = [
            // Pasien
            [
                'id_user' => $pasiens[0]->id_pasien,
                'role' => 'pasien',
                'judul' => 'Jadwal Konsultasi Dokter Aktif',
                'pesan' => 'Sesi telemedisin dengan dr. Budi Santoso, Sp.PD-KGH telah aktif. Silakan buka ruang chat atau video call.',
                'tipe' => 'konsultasi',
                'data' => json_encode(['id_konsultasi' => 1]),
                'is_read' => false,
                'created_at' => $now->copy()->subMinutes(10),
            ],
            [
                'id_user' => $pasiens[0]->id_pasien,
                'role' => 'pasien',
                'judul' => 'Pengingat Minum Obat: Renalvit',
                'pesan' => 'Waktunya minum obat Renalvit 1 tablet setelah sarapan.',
                'tipe' => 'reminder',
                'data' => json_encode(['id_reminder' => 1]),
                'is_read' => true,
                'created_at' => $now->copy()->subHours(2),
            ],
            [
                'id_user' => $pasiens[0]->id_pasien,
                'role' => 'pasien',
                'judul' => 'Pesanan Obat Selesai',
                'pesan' => 'Pesanan obat resep Anda #1 telah selesai dan diterima dengan baik.',
                'tipe' => 'pesanan',
                'data' => json_encode(['id_pembelian' => 1]),
                'is_read' => true,
                'created_at' => $now->copy()->subDays(1),
            ],
            // Dokter
            [
                'id_user' => $dokters[0]->id_dokter,
                'role' => 'dokter',
                'judul' => 'Pesan Chat Baru dari Pasien',
                'pesan' => 'Pasien Rina Wijaya mengirimkan pesan konsultasi baru.',
                'tipe' => 'konsultasi',
                'data' => json_encode(['id_konsultasi' => 2]),
                'is_read' => false,
                'created_at' => $now->copy()->subMinutes(5),
            ],
            [
                'id_user' => $dokters[2]->id_dokter,
                'role' => 'dokter',
                'judul' => 'Sesi Konsultasi Selesai',
                'pesan' => 'Sesi telemedisin dengan pasien Siti Aisyah telah ditandai selesai.',
                'tipe' => 'konsultasi',
                'data' => json_encode(['id_konsultasi' => 1]),
                'is_read' => true,
                'created_at' => $now->copy()->subHours(1),
            ],
            // Apotek
            [
                'id_user' => $apoteks[0]->id_apotek,
                'role' => 'apotek',
                'judul' => 'Pesanan Baru Menunggu Konfirmasi',
                'pesan' => 'Pesanan obat Renalvit dari Rina Wijaya menunggu persetujuan dan pengemasan.',
                'tipe' => 'pesanan',
                'data' => json_encode(['id_pembelian' => 3]),
                'is_read' => false,
                'created_at' => $now->copy()->subMinutes(30),
            ],
            [
                'id_user' => $apoteks[0]->id_apotek,
                'role' => 'apotek',
                'judul' => 'Validasi Resep Dokter Masuk',
                'pesan' => 'Resep Ketosteril untuk Dewi Lestari membutuhkan verifikasi apoteker.',
                'tipe' => 'resep',
                'data' => json_encode(['id_pembelian' => 5]),
                'is_read' => false,
                'created_at' => $now->copy()->subMinutes(15),
            ],
            [
                'id_user' => $apoteks[0]->id_apotek,
                'role' => 'apotek',
                'judul' => 'Peringatan Stok Menipis',
                'pesan' => 'Stok obat Ketosteril tersisa 5 kotak. Segera lakukan pengadaan.',
                'tipe' => 'pesanan',
                'data' => json_encode(['id_obat' => 4]),
                'is_read' => true,
                'created_at' => $now->copy()->subDays(1),
            ],
        ];

        foreach ($notifikasiList as $notif) {
            Notifikasi::create($notif);
        }
    }
}
