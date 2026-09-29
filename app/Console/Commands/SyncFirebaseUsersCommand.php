<?php

namespace App\Console\Commands;

use App\Models\Apotek;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class SyncFirebaseUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'giat:sync-firebase {--password=password123 : Kata sandi yang akan didaftarkan di Firebase untuk akun dummy/lokal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi seluruh akun pengguna dummy/lokal di PostgreSQL ke Firebase Authentication';

    /**
     * Execute the console command.
     */
    public function handle(FirebaseService $firebaseService)
    {
        $this->info('=====================================================');
        $this->info('  GIAT - Sinkronisasi Akun Database Lokal ke Firebase ');
        $this->info('=====================================================');

        if (!$firebaseService->isConfigured()) {
            $this->error('Firebase Web API Key belum diatur di file .env.');
            $this->warn('Harap atur FIREBASE_API_KEY di .env terlebih dahulu.');
            return 1;
        }

        $defaultPassword = (string) $this->option('password');
        $this->info("Menggunakan default password untuk registrasi Firebase: {$defaultPassword}");
        $this->newLine();

        $rows = [];
        $totalSynced = 0;
        $totalFailed = 0;
        $totalExisting = 0;

        // 1. Sync Pasien
        $this->info('-> Memproses Data Pasien...');
        foreach (Pasien::all() as $pasien) {
            $result = $this->syncUser($firebaseService, $pasien, 'Pasien', $defaultPassword);
            $rows[] = $result;
            if ($result['status'] === 'Terdaftar Baru' || $result['status'] === 'Tersinkronkan') {
                $totalSynced++;
            } elseif ($result['status'] === 'Sudah Ada') {
                $totalExisting++;
            } else {
                $totalFailed++;
            }
        }

        // 2. Sync Dokter
        $this->info('-> Memproses Data Dokter...');
        foreach (Dokter::all() as $dokter) {
            $result = $this->syncUser($firebaseService, $dokter, 'Dokter', $defaultPassword);
            $rows[] = $result;
            if ($result['status'] === 'Terdaftar Baru' || $result['status'] === 'Tersinkronkan') {
                $totalSynced++;
            } elseif ($result['status'] === 'Sudah Ada') {
                $totalExisting++;
            } else {
                $totalFailed++;
            }
        }

        // 3. Sync Apotek
        $this->info('-> Memproses Data Apotek...');
        foreach (Apotek::all() as $apotek) {
            $result = $this->syncUser($firebaseService, $apotek, 'Apotek', $defaultPassword);
            $rows[] = $result;
            if ($result['status'] === 'Terdaftar Baru' || $result['status'] === 'Tersinkronkan') {
                $totalSynced++;
            } elseif ($result['status'] === 'Sudah Ada') {
                $totalExisting++;
            } else {
                $totalFailed++;
            }
        }

        $this->newLine();
        $this->table(['Role', 'Nama', 'Email', 'Firebase UID', 'Status'], $rows);

        $this->newLine();
        $this->info("Selesai! Berhasil disinkron: {$totalSynced} | Sudah sinkron: {$totalExisting} | Gagal: {$totalFailed}");

        return 0;
    }

    /**
     * Sync single user model to Firebase.
     */
    protected function syncUser(FirebaseService $firebaseService, $user, string $role, string $defaultPassword): array
    {
        $status = 'Sudah Ada';
        $uid = $user->firebase_uid;

        if (empty($user->firebase_uid)) {
            // Coba daftarkan ke Firebase
            try {
                $fb = $firebaseService->signUpWithEmailPassword($user->email, $defaultPassword, $user->nama);
                $uid = $fb['firebase_uid'];
                $user->firebase_uid = $uid;
                $user->auth_provider = 'firebase';
                $user->save();
                $status = 'Terdaftar Baru';
            } catch (\Throwable $e) {
                // Jika email sudah pernah ada di Firebase, coba signIn untuk dapatkan UID
                try {
                    $fb = $firebaseService->signInWithEmailPassword($user->email, $defaultPassword);
                    $uid = $fb['firebase_uid'];
                    $user->firebase_uid = $uid;
                    $user->auth_provider = 'firebase';
                    $user->save();
                    $status = 'Tersinkronkan';
                } catch (\Throwable $signInErr) {
                    $status = 'Gagal: ' . substr($e->getMessage(), 0, 40);
                }
            }
        }

        return [
            'role' => $role,
            'nama' => $user->nama,
            'email' => $user->email,
            'uid' => $uid ?: '-',
            'status' => $status,
        ];
    }
}
