<?php

namespace Tests\Feature;

use App\Models\Apotek;
use App\Models\Dokter;
use App\Models\Notifikasi;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Reminder;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GiatApiTest extends TestCase
{
    use WithFaker;

    /**
     * Test 1: Public Auth & OTP flow
     */
    public function test_auth_registration_and_login_flow(): void
    {
        $uniqueEmail = 'pasien.test.' . time() . '@gmail.com';

        // 1. Register Pasien
        $registerRes = $this->postJson('/api/auth/register/pasien', [
            'nama' => 'Test Pasien GIAT',
            'email' => $uniqueEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '081234567890',
            'jenis_kelamin' => 'L',
        ]);

        $registerRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi pasien berhasil',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'role', 'access_token', 'token_type'],
            ]);

        // 2. Login
        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $uniqueEmail,
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ]);

        $token = $loginRes->json('data.access_token');
        $this->assertNotEmpty($token);

        // 3. Forgot Password
        $forgotRes = $this->postJson('/api/auth/forgot-password', [
            'email' => $uniqueEmail,
        ]);

        $forgotRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $otp = $forgotRes->json('data.otp');
        $this->assertNotEmpty($otp);

        // 4. Verify OTP
        $verifyRes = $this->postJson('/api/auth/verify-otp', [
            'email' => $uniqueEmail,
            'otp' => $otp,
        ]);

        $verifyRes->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['is_valid' => true]]);

        // 5. Reset Password
        $resetRes = $this->postJson('/api/auth/reset-password', [
            'email' => $uniqueEmail,
            'token' => $otp,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $resetRes->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test 2: Pasien Endpoints
     */
    public function test_pasien_endpoints(): void
    {
        $pasien = Pasien::first() ?? Pasien::create([
            'nama' => 'Pasien Testing',
            'email' => 'pasien.dummy@giat.id',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($pasien);

        // Me
        $this->getJson('/api/me')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Home
        $this->getJson('/api/pasien/home')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Edukasi
        $this->getJson('/api/pasien/edukasi')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Reminder CRUD & Toggle
        $createRem = $this->postJson('/api/pasien/reminder', [
            'nama' => 'Minum Renalvit Pagi',
            'tanggal' => now()->toDateString(),
            'waktu' => '08:00',
            'keterangan' => 'Setelah sarapan',
        ]);

        $createRem->assertStatus(201)->assertJson(['success' => true]);
        $reminderId = $createRem->json('data.id_reminder');

        $this->patchJson("/api/pasien/reminder/{$reminderId}/toggle")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Pantau IMT & Summary
        $this->postJson('/api/pasien/pantau', [
            'bb' => 65.5,
            'tb' => 170,
            'keluhan' => 'Pinggang agak pegal',
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->getJson('/api/pasien/pantau/summary')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // PRAGI Skrining & Chatbot AI
        $this->getJson('/api/pasien/pragi/pertanyaan')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->postJson('/api/pasien/pragi/skrining', [
            'jawaban' => [1 => 'ya', 2 => 'tidak', 3 => 'tidak', 4 => 'tidak', 5 => 'tidak', 6 => 'tidak', 7 => 'tidak', 8 => 'tidak', 9 => 'tidak', 10 => 'tidak'],
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->postJson('/api/pasien/pragi/chat', [
            'pesan' => 'Bagaimana cara mencegah penyakit ginjal dan berapa liter air putih yang harus saya minum?',
        ])->assertStatus(200)->assertJson(['success' => true]);

        // Obat Katalog & Detail
        $katalogRes = $this->getJson('/api/pasien/obat/katalog')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $firstObat = Obat::first();
        if ($firstObat) {
            $this->getJson("/api/pasien/obat/katalog/{$firstObat->id_obat}")
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        }

        // Notifikasi Pasien
        $this->getJson('/api/pasien/notifikasi')
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test 3: Konsultasi Endpoints (Pasien & Dokter)
     */
    public function test_konsultasi_flow(): void
    {
        $pasien = Pasien::first();
        $dokter = Dokter::first();

        $this->assertNotNull($pasien);
        $this->assertNotNull($dokter);

        Sanctum::actingAs($pasien);

        // Kategori Dokter & List
        $this->getJson('/api/konsultasi/kategori-dokter')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/konsultasi/dokter')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson("/api/konsultasi/dokter/{$dokter->id_dokter}")->assertStatus(200)->assertJson(['success' => true]);

        // Booking Dokter
        $booking = $this->postJson('/api/konsultasi/booking', [
            'id_dokter' => $dokter->id_dokter,
            'tanggal_konsultasi' => now()->addDays(1)->format('Y-m-d 10:00:00'),
            'isi_konsultasi' => 'Keluhan sering buang air kecil di malam hari.',
        ]);

        $booking->assertStatus(201)->assertJson(['success' => true]);
        $idKonsultasi = $booking->json('data.id_konsultasi');

        // Bayar Konsultasi
        $this->postJson("/api/konsultasi/{$idKonsultasi}/bayar", [
            'metode_pembayaran' => 'qris',
        ])->assertStatus(200)->assertJson(['success' => true]);

        // Cek Status Bayar
        $this->getJson("/api/konsultasi/{$idKonsultasi}/status-bayar")
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status_pembayaran' => 'lunas']]);

        // Chat Pesan
        $this->postJson("/api/konsultasi/{$idKonsultasi}/pesan", [
            'pesan' => 'Halo dokter, selamat siang.',
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->getJson("/api/konsultasi/{$idKonsultasi}/pesan")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Video Call Room
        $this->getJson("/api/konsultasi/{$idKonsultasi}/video-call")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Selesaikan Konsultasi
        $this->postJson("/api/konsultasi/{$idKonsultasi}/selesai", [
            'catatan_dokter' => 'Pasien dianjurkan menjaga asupan cairan.',
        ])->assertStatus(200)->assertJson(['success' => true]);
    }

    /**
     * Test 4: Dokter Endpoints
     */
    public function test_dokter_endpoints(): void
    {
        $dokter = Dokter::first();
        $this->assertNotNull($dokter);

        Sanctum::actingAs($dokter);

        $this->getJson('/api/dokter/dashboard')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/notifikasi')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/jadwal')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/pasien')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/resep')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/obat-rekomendasi')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/profile')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/dokter/profile/perangkat')->assertStatus(200)->assertJson(['success' => true]);
    }

    /**
     * Test 5: Apotek Endpoints
     */
    public function test_apotek_endpoints(): void
    {
        $apotek = Apotek::first();
        $this->assertNotNull($apotek);

        Sanctum::actingAs($apotek);

        $this->getJson('/api/apotek/dashboard')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/notifikasi')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/reminder')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/pesanan')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/resep')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/obat')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/obat/stock')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/profile')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/jam-operasional')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/area-layanan')->assertStatus(200)->assertJson(['success' => true]);
        $this->patchJson('/api/apotek/status-layanan')->assertStatus(200)->assertJson(['success' => true]);
        $this->getJson('/api/apotek/aktivitas')->assertStatus(200)->assertJson(['success' => true]);
    }
}
