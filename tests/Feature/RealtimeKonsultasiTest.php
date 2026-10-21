<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Konsultasi;
use App\Models\KonsultasiPembayaran;
use App\Models\Pasien;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeKonsultasiTest extends TestCase
{
    protected Pasien $pasien;
    protected Dokter $dokter;
    protected Konsultasi $konsultasi;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake all external HTTP requests (Firebase RTDB, etc.)
        Http::fake([
            '*.firebaseio.com/*' => Http::response(['name' => 'ok'], 200),
            '*.firebasedatabase.app/*' => Http::response(['name' => 'ok'], 200),
        ]);

        // Setup test Pasien & User
        $userPasien = User::where('role', 'pasien')->first();
        if (! $userPasien) {
            $userPasien = User::create([
                'id' => (string) Str::uuid(),
                'email' => 'pasien.rt.' . Str::random(6) . '@giat.id',
                'password' => bcrypt('password123'),
                'role' => 'pasien',
            ]);
        }
        $this->pasien = Pasien::find($userPasien->id) ?? Pasien::create([
            'id_pasien' => $userPasien->id,
            'nama' => 'Pasien Realtime Test',
            'no_hp' => '081299887766',
        ]);

        // Setup test Dokter & User
        $userDokter = User::where('role', 'dokter')->first();
        if (! $userDokter) {
            $userDokter = User::create([
                'id' => (string) Str::uuid(),
                'email' => 'dokter.rt.' . Str::random(6) . '@giat.id',
                'password' => bcrypt('password123'),
                'role' => 'dokter',
            ]);
        }
        $this->dokter = Dokter::find($userDokter->id) ?? Dokter::create([
            'id_dokter' => $userDokter->id,
            'nama' => 'dr. Realtime Sp.PD',
            'spesialisasi' => 'Dokter Spesialis Penyakit Dalam',
            'tarif_konsultasi' => 75000,
        ]);

        // Setup active consultation session
        $this->konsultasi = Konsultasi::create([
            'id' => (string) Str::uuid(),
            'id_pasien' => $this->pasien->id_pasien,
            'id_dokter' => $this->dokter->id_dokter,
            'tanggal_konsultasi' => now(),
            'status' => 'berlangsung',
            'keluhan_awal' => 'Keluhan tes konsultasi realtime',
            'jenis_layanan' => 'chat',
        ]);

        // Setup pembayaran lunas
        KonsultasiPembayaran::create([
            'id' => (string) Str::uuid(),
            'id_konsultasi' => $this->konsultasi->id,
            'no_invoice' => 'INV-TEST-' . strtoupper(Str::random(8)),
            'jumlah_bayar' => 75000,
            'metode_pembayaran' => 'qris',
            'status_bayar' => 'lunas',
            'waktu_bayar' => now(),
        ]);
    }

    /**
     * Test mendapatkan konfigurasi Firebase Channel realtime.
     */
    public function test_get_firebase_channel_info(): void
    {
        Sanctum::actingAs($this->pasien);

        $response = $this->getJson("/api/konsultasi/{$this->konsultasi->id}/firebase-channel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Konfigurasi channel realtime Firebase',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id_konsultasi',
                    'database_url',
                    'project_id',
                    'channel_path',
                    'messages_path',
                    'typing_path',
                    'call_signaling_path',
                    'session_info_path',
                ],
            ]);

        $this->assertEquals("konsultasi/{$this->konsultasi->id}/messages", $response->json('data.messages_path'));
    }

    /**
     * Test pengiriman pesan chat dan push ke Firebase Realtime Database.
     */
    public function test_send_message_with_realtime_firebase_push(): void
    {
        Sanctum::actingAs($this->pasien);

        $payload = [
            'pesan' => 'Halo dokter, ini hasil lab tes fungsi ginjal saya.',
            'tipe' => 'text',
        ];

        $response = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/pesan", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Pesan terkirim',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id_pesan',
                    'id_konsultasi',
                    'sender_id',
                    'pesan',
                ],
            ]);

        $this->assertDatabaseHas('konsultasi_pesan', [
            'id_konsultasi' => $this->konsultasi->id,
            'pesan' => $payload['pesan'],
        ]);
    }

    /**
     * Test validasi pengiriman pesan chat.
     */
    public function test_send_message_validation(): void
    {
        Sanctum::actingAs($this->pasien);

        $response = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/pesan", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pesan']);
    }

    /**
     * Test update indikator sedang mengetik (Typing Indicator).
     */
    public function test_typing_indicator_update(): void
    {
        Sanctum::actingAs($this->pasien);

        // Sedang mengetik (true)
        $response = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/typing", [
            'is_typing' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                    'role' => 'pasien',
                    'is_typing' => true,
                ],
            ]);

        // Berhenti mengetik (false)
        $responseOff = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/typing", [
            'is_typing' => false,
        ]);

        $responseOff->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                    'role' => 'pasien',
                    'is_typing' => false,
                ],
            ]);
    }

    /**
     * Test inisiasi video call (Start Video Call) dengan status 'ringing'.
     */
    public function test_start_video_call_creates_room_and_signaling(): void
    {
        Sanctum::actingAs($this->dokter);

        $response = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/video-call/start", [
            'notify_chat' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                    'status_panggilan' => 'ringing',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id_konsultasi',
                    'room_id',
                    'video_call_url',
                    'status_panggilan',
                    'caller' => ['id', 'nama', 'role'],
                ],
            ]);

        $roomId = $response->json('data.room_id');
        $this->assertStringStartsWith('giat-room-', $roomId);
        $this->assertStringContainsString('https://meet.jit.si/', $response->json('data.video_call_url'));

        $this->assertDatabaseHas('konsultasi_video_session', [
            'id_konsultasi' => $this->konsultasi->id,
            'room_id' => $roomId,
            'status_panggilan' => 'ringing',
        ]);
    }

    /**
     * Test respon sinyal video call (accept, reject, busy).
     */
    public function test_signal_video_call_responses(): void
    {
        Sanctum::actingAs($this->pasien);

        // 1. Terima panggilan (accept -> connected)
        $acceptRes = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/video-call/signal", [
            'signal' => 'accept',
        ]);

        $acceptRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                    'status' => 'connected',
                ],
            ]);

        $this->assertDatabaseHas('konsultasi_video_session', [
            'id_konsultasi' => $this->konsultasi->id,
            'status_panggilan' => 'connected',
        ]);

        // 2. Tolak panggilan (reject -> rejected)
        $rejectRes = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/video-call/signal", [
            'signal' => 'reject',
        ]);

        $rejectRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'rejected',
                ],
            ]);

        $this->assertDatabaseHas('konsultasi_video_session', [
            'id_konsultasi' => $this->konsultasi->id,
            'status_panggilan' => 'rejected',
        ]);

        // 3. Sibuk (busy -> busy)
        $busyRes = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/video-call/signal", [
            'signal' => 'busy',
        ]);

        $busyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'busy',
                ],
            ]);
    }

    /**
     * Test ambil info room & status video call saat ini.
     */
    public function test_get_video_call_status(): void
    {
        Sanctum::actingAs($this->pasien);

        $response = $this->getJson("/api/konsultasi/{$this->konsultasi->id}/video-call");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id_konsultasi',
                    'room_id',
                    'video_call_url',
                    'status_panggilan',
                    'dokter',
                    'pasien',
                ],
            ]);
    }

    /**
     * Test mengakhiri sesi video call (End Video Call) dan mencatat durasi.
     */
    public function test_end_video_call_and_store_duration(): void
    {
        Sanctum::actingAs($this->dokter);

        $response = $this->postJson("/api/konsultasi/{$this->konsultasi->id}/video-call/end", [
            'durasi_menit' => 25,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_konsultasi' => $this->konsultasi->id,
                    'status_panggilan' => 'berakhir',
                    'durasi_menit' => 25,
                ],
            ]);

        $this->assertDatabaseHas('konsultasi_video_session', [
            'id_konsultasi' => $this->konsultasi->id,
            'status_panggilan' => 'berakhir',
            'durasi_menit' => 25,
        ]);
    }
}
