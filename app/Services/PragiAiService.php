<?php

namespace App\Services;

use App\Models\PragiPertanyaan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PragiAiService
{
    /**
     * Get the authoritative 16 AI PRAGI questions catalog.
     * Matches the exact 16 clinical/demographic features expected by the AI PRAGI ML pipeline.
     */
    public static function getQuestionsCatalog(): array
    {
        $questions = self::get16Questions();
        $samplePayload = self::getExactSamplePayload();
        $encodedPayload = self::encodePayload($samplePayload);

        return [
            'judul' => 'Kuesioner 16 Pertanyaan Skrining AI PRAGI',
            'deskripsi' => 'Daftar 16 pertanyaan fitur klinis, gaya hidup, dan demografis terintegrasi untuk model Machine Learning AI PRAGI.',
            'total_pertanyaan' => count($questions),
            'pertanyaan' => $questions,
            'contoh_hasil_jawaban_raw' => $samplePayload,
            'contoh_hasil_jawaban_encoded' => $encodedPayload,
            'panduan_encoding' => [
                'HeartDisease' => '0: No (Tidak), 1: Yes (Ya)',
                'BMI' => 'Nilai desimal (float), misal: 26.45 (kg/m²)',
                'Smoking' => '0: No (Tidak), 1: Yes (Ya)',
                'AlcoholDrinking' => '0: No (Tidak), 1: Yes (Ya)',
                'Stroke' => '0: No (Tidak), 1: Yes (Ya)',
                'PhysicalHealth' => 'Nilai integer 0 - 30 (hari sakit fisik dalam 30 hari terakhir)',
                'MentalHealth' => 'Nilai integer 0 - 30 (hari stres/kondisi mental kurang baik dalam 30 hari)',
                'DiffWalking' => '0: No (Tidak), 1: Yes (Ya)',
                'Sex' => '0: Female (Perempuan), 1: Male (Laki-laki)',
                'AgeCategory' => '0: 18-24, 1: 25-29, 2: 30-34, 3: 35-39, 4: 40-44, 5: 45-49, 6: 50-54, 7: 55-59, 8: 60-64, 9: 65-69, 10: 70-74, 11: 75-79, 12: 80 or older',
                'Race' => 'Menggunakan pd.get_dummies(..., drop_first=True). American Indian/Alaskan Native adalah baseline (semua Race_* bernilai 0). Memilih Asian, Black, Hispanic, Other, atau White akan mengeset kolom Race_* yang sesuai bernilai 1.',
                'Diabetic' => '0: No (Tidak menderita diabetes), 1: Yes / Diabetes (mencakup: Yes, No, borderline diabetes, dan Yes (during pregnancy))',
                'PhysicalActivity' => '0: No (Tidak), 1: Yes (Ya)',
                'GenHealth' => '0: Poor (Buruk), 1: Fair (Cukup), 2: Good (Baik), 3: Very good (Sangat Baik), 4: Excellent (Sangat Bagus / Sempurna)',
                'SleepTime' => 'Nilai integer jam tidur harian dalam 24 jam (misal: 5)',
                'Asthma' => '0: No (Tidak), 1: Yes (Ya)',
            ],
        ];
    }

    /**
     * Exact 16 Questions list.
     */
    public static function get16Questions(): array
    {
        return [
            // 1. HeartDisease
            [
                'id' => 1,
                'kode_field' => 'HeartDisease',
                'pertanyaan' => 'Apakah Anda pernah didiagnosis menderita penyakit jantung koroner atau serangan jantung?',
                'pertanyaan_en' => 'Have you ever been told you had coronary heart disease or heart attack?',
                'kategori' => 'Kondisi Medis & Komorbid',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 2. BMI
            [
                'id' => 2,
                'kode_field' => 'BMI',
                'pertanyaan' => 'Berapa Indeks Massa Tubuh (BMI) Anda? (Berat Badan [kg] / (Tinggi Badan [m])²)',
                'pertanyaan_en' => 'Body Mass Index (BMI)',
                'kategori' => 'Pengukuran Fisik',
                'tipe_input' => 'number',
                'satuan' => 'kg/m²',
                'step' => 0.01,
                'min' => 10.0,
                'max' => 60.0,
                'nilai_default' => 26.45,
                'opsi_jawaban' => null,
            ],

            // 3. Smoking
            [
                'id' => 3,
                'kode_field' => 'Smoking',
                'pertanyaan' => 'Apakah Anda pernah merokok setidaknya 100 batang rokok sepanjang hidup Anda?',
                'pertanyaan_en' => 'Have you smoked at least 100 cigarettes in your entire life?',
                'kategori' => 'Gaya Hidup',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 4. AlcoholDrinking
            [
                'id' => 4,
                'kode_field' => 'AlcoholDrinking',
                'pertanyaan' => 'Apakah Anda mengonsumsi alkohol dalam jumlah berat (pria >14 gelas/minggu, wanita >7 gelas/minggu)?',
                'pertanyaan_en' => 'Are you a heavy alcohol drinker?',
                'kategori' => 'Gaya Hidup',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 5. Stroke
            [
                'id' => 5,
                'kode_field' => 'Stroke',
                'pertanyaan' => 'Apakah Anda pernah didiagnosis mengalami stroke oleh dokter?',
                'pertanyaan_en' => 'Have you ever been told you had a stroke?',
                'kategori' => 'Kondisi Medis & Komorbid',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 6. PhysicalHealth
            [
                'id' => 6,
                'kode_field' => 'PhysicalHealth',
                'pertanyaan' => 'Dalam 30 hari terakhir, berapa hari kondisi kesehatan fisik Anda terasa tidak baik/sakit?',
                'pertanyaan_en' => 'How many days during the past 30 days was your physical health not good?',
                'kategori' => 'Kondisi Fisik',
                'tipe_input' => 'number',
                'satuan' => 'hari',
                'min' => 0,
                'max' => 30,
                'nilai_default' => 0,
                'opsi_jawaban' => null,
            ],

            // 7. MentalHealth
            [
                'id' => 7,
                'kode_field' => 'MentalHealth',
                'pertanyaan' => 'Dalam 30 hari terakhir, berapa hari kondisi kesehatan mental (stres/depresi) Anda terasa tidak baik?',
                'pertanyaan_en' => 'How many days during the past 30 days was your mental health not good?',
                'kategori' => 'Kesehatan Mental',
                'tipe_input' => 'number',
                'satuan' => 'hari',
                'min' => 0,
                'max' => 30,
                'nilai_default' => 0,
                'opsi_jawaban' => null,
            ],

            // 8. DiffWalking
            [
                'id' => 8,
                'kode_field' => 'DiffWalking',
                'pertanyaan' => 'Apakah Anda mengalami kesulitan yang serius saat berjalan atau menaiki tangga?',
                'pertanyaan_en' => 'Do you have serious difficulty walking or climbing stairs?',
                'kategori' => 'Kondisi Fisik',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 9. Sex
            [
                'id' => 9,
                'kode_field' => 'Sex',
                'pertanyaan' => 'Apa jenis kelamin biologis Anda?',
                'pertanyaan_en' => 'What is your biological sex?',
                'kategori' => 'Demografi',
                'tipe_input' => 'select',
                'nilai_default' => 'Female',
                'opsi_jawaban' => [
                    ['label' => 'Female (Perempuan)', 'nilai' => 'Female', 'nilai_numeric' => 0],
                    ['label' => 'Male (Laki-laki)', 'nilai' => 'Male', 'nilai_numeric' => 1],
                ],
            ],

            // 10. AgeCategory
            [
                'id' => 10,
                'kode_field' => 'AgeCategory',
                'pertanyaan' => 'Berapa rentang kelompok usia Anda saat ini?',
                'pertanyaan_en' => 'What is your age category?',
                'kategori' => 'Demografi',
                'tipe_input' => 'select',
                'nilai_default' => '80 or older',
                'opsi_jawaban' => [
                    ['label' => '18-24 tahun', 'nilai' => '18-24', 'nilai_numeric' => 0],
                    ['label' => '25-29 tahun', 'nilai' => '25-29', 'nilai_numeric' => 1],
                    ['label' => '30-34 tahun', 'nilai' => '30-34', 'nilai_numeric' => 2],
                    ['label' => '35-39 tahun', 'nilai' => '35-39', 'nilai_numeric' => 3],
                    ['label' => '40-44 tahun', 'nilai' => '40-44', 'nilai_numeric' => 4],
                    ['label' => '45-49 tahun', 'nilai' => '45-49', 'nilai_numeric' => 5],
                    ['label' => '50-54 tahun', 'nilai' => '50-54', 'nilai_numeric' => 6],
                    ['label' => '55-59 tahun', 'nilai' => '55-59', 'nilai_numeric' => 7],
                    ['label' => '60-64 tahun', 'nilai' => '60-64', 'nilai_numeric' => 8],
                    ['label' => '65-69 tahun', 'nilai' => '65-69', 'nilai_numeric' => 9],
                    ['label' => '70-74 tahun', 'nilai' => '70-74', 'nilai_numeric' => 10],
                    ['label' => '75-79 tahun', 'nilai' => '75-79', 'nilai_numeric' => 11],
                    ['label' => '80 tahun atau lebih (80 or older)', 'nilai' => '80 or older', 'nilai_numeric' => 12],
                ],
            ],

            // 11. Race
            [
                'id' => 11,
                'kode_field' => 'Race',
                'pertanyaan' => 'Apa latar belakang ras atau etnis Anda?',
                'pertanyaan_en' => 'What is your race / ethnicity?',
                'kategori' => 'Demografi',
                'tipe_input' => 'select',
                'nilai_default' => 'White',
                'metode_encoding' => 'pd.get_dummies(..., drop_first=True)',
                'catatan' => 'Salah satu ras menjadi kategori acuan/baseline dengan nilai 0 di semua kolom turunan Race_*. Memilih ras tertentu mengeset kolom terkait menjadi 1.',
                'opsi_jawaban' => [
                    [
                        'label' => 'American Indian/Alaskan Native (Acuan / Baseline)',
                        'nilai' => 'American Indian/Alaskan Native',
                        'dummy_values' => [
                            'Race_Asian' => 0,
                            'Race_Black' => 0,
                            'Race_Hispanic' => 0,
                            'Race_Other' => 0,
                            'Race_White' => 0,
                        ],
                    ],
                    [
                        'label' => 'Asian',
                        'nilai' => 'Asian',
                        'dummy_values' => [
                            'Race_Asian' => 1,
                            'Race_Black' => 0,
                            'Race_Hispanic' => 0,
                            'Race_Other' => 0,
                            'Race_White' => 0,
                        ],
                    ],
                    [
                        'label' => 'Black',
                        'nilai' => 'Black',
                        'dummy_values' => [
                            'Race_Asian' => 0,
                            'Race_Black' => 1,
                            'Race_Hispanic' => 0,
                            'Race_Other' => 0,
                            'Race_White' => 0,
                        ],
                    ],
                    [
                        'label' => 'Hispanic',
                        'nilai' => 'Hispanic',
                        'dummy_values' => [
                            'Race_Asian' => 0,
                            'Race_Black' => 0,
                            'Race_Hispanic' => 1,
                            'Race_Other' => 0,
                            'Race_White' => 0,
                        ],
                    ],
                    [
                        'label' => 'Other',
                        'nilai' => 'Other',
                        'dummy_values' => [
                            'Race_Asian' => 0,
                            'Race_Black' => 0,
                            'Race_Hispanic' => 0,
                            'Race_Other' => 1,
                            'Race_White' => 0,
                        ],
                    ],
                    [
                        'label' => 'White',
                        'nilai' => 'White',
                        'dummy_values' => [
                            'Race_Asian' => 0,
                            'Race_Black' => 0,
                            'Race_Hispanic' => 0,
                            'Race_Other' => 0,
                            'Race_White' => 1,
                        ],
                    ],
                ],
            ],

            // 12. Diabetic
            [
                'id' => 12,
                'kode_field' => 'Diabetic',
                'pertanyaan' => 'Apakah Anda memiliki riwayat diabetes melitus (gula darah tinggi)?',
                'pertanyaan_en' => 'Have you ever been told you have diabetes?',
                'kategori' => 'Kondisi Medis & Komorbid',
                'tipe_input' => 'select',
                'nilai_default' => 'No, borderline diabetes',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak menderita diabetes)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'No, borderline diabetes (Pradiabetes)', 'nilai' => 'No, borderline diabetes', 'nilai_numeric' => 1],
                    ['label' => 'Yes (Menderita diabetes)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                    ['label' => 'Yes (during pregnancy) (Diabetes saat kehamilan)', 'nilai' => 'Yes (during pregnancy)', 'nilai_numeric' => 1],
                ],
            ],

            // 13. PhysicalActivity
            [
                'id' => 13,
                'kode_field' => 'PhysicalActivity',
                'pertanyaan' => 'Apakah Anda melakukan aktivitas fisik atau berolahraga dalam 30 hari terakhir (di luar pekerjaan rutin)?',
                'pertanyaan_en' => 'Did you participate in any physical activities or exercise in the past 30 days?',
                'kategori' => 'Gaya Hidup',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],

            // 14. GenHealth
            [
                'id' => 14,
                'kode_field' => 'GenHealth',
                'pertanyaan' => 'Secara umum, bagaimana Anda menilai kondisi kesehatan umum Anda saat ini?',
                'pertanyaan_en' => 'Would you say that in general your health is:',
                'kategori' => 'Kesehatan Umum',
                'tipe_input' => 'select',
                'nilai_default' => 'Fair',
                'opsi_jawaban' => [
                    ['label' => 'Poor (Buruk)', 'nilai' => 'Poor', 'nilai_numeric' => 0],
                    ['label' => 'Fair (Cukup)', 'nilai' => 'Fair', 'nilai_numeric' => 1],
                    ['label' => 'Good (Baik)', 'nilai' => 'Good', 'nilai_numeric' => 2],
                    ['label' => 'Very good (Sangat Baik)', 'nilai' => 'Very good', 'nilai_numeric' => 3],
                    ['label' => 'Excellent (Sangat Bagus / Sempurna)', 'nilai' => 'Excellent', 'nilai_numeric' => 4],
                ],
            ],

            // 15. SleepTime
            [
                'id' => 15,
                'kode_field' => 'SleepTime',
                'pertanyaan' => 'Berapa rata-rata jam tidur Anda dalam kurun waktu 24 jam sehari?',
                'pertanyaan_en' => 'On average, how many hours of sleep do you get in a 24-hour period?',
                'kategori' => 'Gaya Hidup',
                'tipe_input' => 'number',
                'satuan' => 'jam',
                'min' => 1,
                'max' => 24,
                'nilai_default' => 5,
                'opsi_jawaban' => null,
            ],

            // 16. Asthma
            [
                'id' => 16,
                'kode_field' => 'Asthma',
                'pertanyaan' => 'Apakah Anda memiliki riwayat atau sedang menderita penyakit asma?',
                'pertanyaan_en' => 'Have you ever been told that you have asthma?',
                'kategori' => 'Kondisi Medis & Komorbid',
                'tipe_input' => 'binary',
                'nilai_default' => 'No',
                'opsi_jawaban' => [
                    ['label' => 'No (Tidak)', 'nilai' => 'No', 'nilai_numeric' => 0],
                    ['label' => 'Yes (Ya)', 'nilai' => 'Yes', 'nilai_numeric' => 1],
                ],
            ],
        ];
    }

    /**
     * Exact sample JSON matching the user requirement.
     */
    public static function getExactSamplePayload(): array
    {
        return [
            'HeartDisease' => 'No',
            'BMI' => 26.45,
            'Smoking' => 'No',
            'AlcoholDrinking' => 'No',
            'Stroke' => 'No',
            'PhysicalHealth' => 0,
            'MentalHealth' => 0,
            'DiffWalking' => 'No',
            'Sex' => 'Female',
            'AgeCategory' => '80 or older',
            'Race' => 'White',
            'Diabetic' => 'No, borderline diabetes',
            'PhysicalActivity' => 'No',
            'GenHealth' => 'Fair',
            'SleepTime' => 5,
            'Asthma' => 'No',
        ];
    }

    /**
     * Convert raw answers into encoded numerical / one-hot representation for Machine Learning model.
     */
    public static function encodePayload(array $raw): array
    {
        // 1. Binary mappings
        $binaryMap = ['yes' => 1, '1' => 1, 'no' => 0, '0' => 0];

        $encodeBinary = function ($val) use ($binaryMap) {
            $key = strtolower(trim((string) $val));
            return $binaryMap[$key] ?? 0;
        };

        // 2. Sex: Female=0, Male=1
        $sexVal = strtolower(trim((string) ($raw['Sex'] ?? 'female'))) === 'male' ? 1 : 0;

        // 3. AgeCategory: 0..12
        $ageCategories = [
            '18-24' => 0, '25-29' => 1, '30-34' => 2, '35-39' => 3, '40-44' => 4,
            '45-49' => 5, '50-54' => 6, '55-59' => 7, '60-64' => 8, '65-69' => 9,
            '70-74' => 10, '75-79' => 11, '80 or older' => 12,
        ];
        $ageVal = $ageCategories[trim((string) ($raw['AgeCategory'] ?? '18-24'))] ?? 0;

        // 4. Diabetic: No=0, Others=1
        $diabeticRaw = strtolower(trim((string) ($raw['Diabetic'] ?? 'no')));
        $diabeticVal = ($diabeticRaw === 'no' || $diabeticRaw === '0') ? 0 : 1;

        // 5. GenHealth: Poor=0, Fair=1, Good=2, Very good=3, Excellent=4
        $genHealthMap = [
            'poor' => 0, 'fair' => 1, 'good' => 2, 'very good' => 3, 'excellent' => 4,
        ];
        $genHealthVal = $genHealthMap[strtolower(trim((string) ($raw['GenHealth'] ?? 'good')))] ?? 2;

        // 6. Race: pd.get_dummies(..., drop_first=True)
        // Baseline = American Indian/Alaskan Native (all 0)
        $raceRaw = trim((string) ($raw['Race'] ?? ''));
        $dummies = [
            'Race_Asian' => ($raceRaw === 'Asian') ? 1 : 0,
            'Race_Black' => ($raceRaw === 'Black') ? 1 : 0,
            'Race_Hispanic' => ($raceRaw === 'Hispanic') ? 1 : 0,
            'Race_Other' => ($raceRaw === 'Other') ? 1 : 0,
            'Race_White' => ($raceRaw === 'White') ? 1 : 0,
        ];

        return array_merge([
            'HeartDisease' => $encodeBinary($raw['HeartDisease'] ?? 'No'),
            'BMI' => (float) ($raw['BMI'] ?? 24.0),
            'Smoking' => $encodeBinary($raw['Smoking'] ?? 'No'),
            'AlcoholDrinking' => $encodeBinary($raw['AlcoholDrinking'] ?? 'No'),
            'Stroke' => $encodeBinary($raw['Stroke'] ?? 'No'),
            'PhysicalHealth' => (int) ($raw['PhysicalHealth'] ?? 0),
            'MentalHealth' => (int) ($raw['MentalHealth'] ?? 0),
            'DiffWalking' => $encodeBinary($raw['DiffWalking'] ?? 'No'),
            'Sex' => $sexVal,
            'AgeCategory' => $ageVal,
        ], $dummies, [
            'Diabetic' => $diabeticVal,
            'PhysicalActivity' => $encodeBinary($raw['PhysicalActivity'] ?? 'No'),
            'GenHealth' => $genHealthVal,
            'SleepTime' => (int) ($raw['SleepTime'] ?? 7),
            'Asthma' => $encodeBinary($raw['Asthma'] ?? 'No'),
        ]);
    }

    /**
     * Sync interactive AI PRAGI questions into database table `pragi_pertanyaan`.
     */
    public static function syncDatabaseQuestions(): int
    {
        $questions = self::get16Questions();
        $synced = 0;

        foreach ($questions as $q) {
            PragiPertanyaan::updateOrCreate(
                ['kode_field' => $q['kode_field']],
                [
                    'id' => (string) Str::uuid(),
                    'pertanyaan' => $q['pertanyaan'],
                    'pertanyaan_en' => $q['pertanyaan_en'] ?? null,
                    'kategori' => $q['kategori'],
                    'tipe_input' => $q['tipe_input'],
                    'opsi_jawaban' => $q['opsi_jawaban'],
                    'urutan' => $q['id'],
                    'bobot' => 1,
                    'is_active' => true,
                ]
            );
            $synced++;
        }

        return $synced;
    }

    /**
     * Evaluate screening answers.
     */
    public static function evaluateScreening(array $jawaban): array
    {
        $encoded = self::encodePayload($jawaban);
        $skor = 0;
        $faktorRisiko = [];

        // Check if legacy numeric keys format (1 => 'ya', 2 => 'tidak', ...)
        if (isset($jawaban[1]) || isset($jawaban['1'])) {
            $bobotMap = [1 => 2, 2 => 2, 3 => 2, 4 => 1, 5 => 2, 6 => 1, 7 => 1, 8 => 1, 9 => 1, 10 => 1];
            foreach ($jawaban as $qId => $ans) {
                $isYes = in_array(strtolower((string) $ans), ['1', 'true', 'ya', 'yes'], true);
                if ($isYes && isset($bobotMap[$qId])) {
                    $skor += $bobotMap[$qId];
                }
            }
        } else {
            // Evaluasi berdasarkan 16 fitur
            if ($encoded['HeartDisease'] === 1) {
                $skor += 3;
                $faktorRisiko[] = 'Penyakit Jantung Koroner';
            }
            if ($encoded['Stroke'] === 1) {
                $skor += 3;
                $faktorRisiko[] = 'Riwayat Stroke';
            }
            if ($encoded['Diabetic'] === 1) {
                $skor += 3;
                $faktorRisiko[] = 'Riwayat Diabetes / Pradiabetes';
            }
            if ($encoded['DiffWalking'] === 1) {
                $skor += 2;
                $faktorRisiko[] = 'Keterbatasan Berjalan / Mobilitas Fisik';
            }
            if ($encoded['Smoking'] === 1) {
                $skor += 1;
                $faktorRisiko[] = 'Riwayat Merokok Aktif';
            }
            if ($encoded['AlcoholDrinking'] === 1) {
                $skor += 1;
                $faktorRisiko[] = 'Konsumsi Alkohol Berat';
            }
            if ($encoded['PhysicalActivity'] === 0) {
                $skor += 1;
                $faktorRisiko[] = 'Kurang Aktivitas Fisik (Sedentari)';
            }
            if ($encoded['BMI'] >= 30.0) {
                $skor += 2;
                $faktorRisiko[] = 'Obesitas (BMI >= 30: ' . $encoded['BMI'] . ')';
            } elseif ($encoded['BMI'] >= 25.0) {
                $skor += 1;
                $faktorRisiko[] = 'Kelebihan Berat Badan / Overweight (BMI: ' . $encoded['BMI'] . ')';
            }
            if ($encoded['GenHealth'] <= 1) { // Poor or Fair
                $skor += 2;
                $faktorRisiko[] = 'Kondisi Kesehatan Umum Menurun (Fair/Poor)';
            }
            if ($encoded['AgeCategory'] >= 8) { // 60+ yo
                $skor += 2;
                $faktorRisiko[] = 'Usia Lanjut (60 tahun ke atas)';
            } elseif ($encoded['AgeCategory'] >= 5) { // 45+ yo
                $skor += 1;
                $faktorRisiko[] = 'Kelompok Usia Menengah (45-59 tahun)';
            }
            if ($encoded['SleepTime'] < 6 || $encoded['SleepTime'] > 9) {
                $skor += 1;
                $faktorRisiko[] = 'Pola Waktu Tidur Tidak Optimal (' . $encoded['SleepTime'] . ' jam/hari)';
            }
            if ($encoded['Asthma'] === 1) {
                $skor += 1;
                $faktorRisiko[] = 'Riwayat Penyakit Asma';
            }
        }

        if ($skor <= 2) {
            $prediksi = 'Risiko Rendah CKD';
            $kategoriRisiko = 'rendah';
            $rekomendasi = 'Fungsi ginjal dan profil kardiovaskular Anda saat ini dinilai stabil dan berisiko rendah. Pertahankan pola hidup sehat: konsumsi air putih minimal 2 liter/hari, batasi konsumsi garam dan gula berlebih, hindari penggunaan obat pereda nyeri (NSAID) tanpa resep, serta lakukan pemeriksaan urin dan tekanan darah berkala.';
        } elseif ($skor <= 6) {
            $prediksi = 'Risiko Sedang CKD';
            $kategoriRisiko = 'sedang';
            $rekomendasi = 'Terdeteksi beberapa indikator faktor risiko ginjal dan kardiovaskular (skor: ' . $skor . '). Disarankan untuk mengontrol tekanan darah dan kadar gula darah secara rutin, mencatat kondisi kesehatan harian di menu Pantau, serta jadwalkan konsultasi dokter spesialis di GIAT.';
        } else {
            $prediksi = 'Risiko Tinggi CKD';
            $kategoriRisiko = 'tinggi';
            $rekomendasi = 'PERINGATAN: Terdeteksi komorbiditas dan faktor risiko signifikan penurunan fungsi ginjal (skor: ' . $skor . '). Segera jadwalkan konsultasi dengan Dokter Ginjal (Nefrolog) atau Dokter Penyakit Dalam melalui telemedisin GIAT untuk pemeriksaan laboratorium ureum, kreatinin, dan laju filtrasi glomerulus (eGFR).';
        }

        return [
            'skor' => $skor,
            'kategori_risiko' => $kategoriRisiko,
            'prediksi' => $prediksi,
            'rekomendasi' => $rekomendasi,
            'faktor_risiko_teridentifikasi' => $faktorRisiko,
            'fitur_terencoded' => $encoded,
        ];
    }

    /**
     * Normalize & sanitize 16 input features to match the exact Python ML API expectations.
     */
    public static function normalize16Features(array $raw): array
    {
        return [
            'HeartDisease' => self::cleanBinary($raw['HeartDisease'] ?? 'No'),
            'BMI' => round((float) ($raw['BMI'] ?? 24.0), 2),
            'Smoking' => self::cleanBinary($raw['Smoking'] ?? 'No'),
            'AlcoholDrinking' => self::cleanBinary($raw['AlcoholDrinking'] ?? 'No'),
            'Stroke' => self::cleanBinary($raw['Stroke'] ?? 'No'),
            'PhysicalHealth' => max(0, min(30, (int) ($raw['PhysicalHealth'] ?? 0))),
            'MentalHealth' => max(0, min(30, (int) ($raw['MentalHealth'] ?? 0))),
            'DiffWalking' => self::cleanBinary($raw['DiffWalking'] ?? 'No'),
            'Sex' => self::cleanSex($raw['Sex'] ?? 'Female'),
            'AgeCategory' => self::cleanAgeCategory($raw['AgeCategory'] ?? '18-24'),
            'Race' => self::cleanRace($raw['Race'] ?? 'White'),
            'Diabetic' => self::cleanDiabetic($raw['Diabetic'] ?? 'No'),
            'PhysicalActivity' => self::cleanBinary($raw['PhysicalActivity'] ?? 'No'),
            'GenHealth' => self::cleanGenHealth($raw['GenHealth'] ?? 'Good'),
            'SleepTime' => max(1, min(24, (int) ($raw['SleepTime'] ?? 7))),
            'Asthma' => self::cleanBinary($raw['Asthma'] ?? 'No'),
        ];
    }

    /**
     * Transmit 16-feature payload to external Python ML API.
     * Includes automated graceful fallback to internal medical rule engine if Python API is offline.
     */
    public static function transmitToPythonApi(array $rawPayload): array
    {
        $cleanPayload = self::normalize16Features($rawPayload);
        $encodedPayload = self::encodePayload($cleanPayload);
        $internalEval = self::evaluateScreening($cleanPayload);

        $url = config('services.pragi_ai.url') ?: env('PRAGI_AI_ML_URL');

        // Jika URL API Python belum dikonfigurasi di .env
        if (empty($url)) {
            $fallbackProbCkd = round($internalEval['skor'] / 15.0, 4);
            $fallbackProbNoCkd = round(1.0 - $fallbackProbCkd, 4);
            $fallbackConf = round($fallbackProbCkd * 100.0, 2);
            $fallbackPred = $internalEval['kategori_risiko'] === 'tinggi' ? 1 : 0;
            $fallbackLabel = $fallbackPred === 1 ? 'Berisiko Penyakit Ginjal Kronis (CKD)' : 'Tidak Berisiko Penyakit Ginjal Kronis (Stabil)';

            return [
                'status' => 'fallback',
                'sumber_prediksi' => 'internal_rule_engine',
                'is_fallback' => true,
                'status_transmisi' => 'url_not_configured',
                'pesan_sistem' => 'PRAGI_AI_ML_URL belum diatur di .env. Prediksi diproses oleh Engine Evaluasi Cerdas GIAT.',
                'skor' => $internalEval['skor'],
                'kategori_risiko' => $internalEval['kategori_risiko'],
                'hasil_prediksi' => $fallbackLabel,
                'label' => $fallbackLabel,
                'prediction' => $fallbackPred,
                'confidence_percent' => $fallbackConf,
                'probabilitas' => $fallbackProbCkd,
                'probability' => [
                    'ckd' => $fallbackProbCkd,
                    'no_ckd' => $fallbackProbNoCkd,
                ],
                'rekomendasi' => $internalEval['rekomendasi'],
                'faktor_risiko_teridentifikasi' => $internalEval['faktor_risiko_teridentifikasi'],
                'fitur_terencoded' => $encodedPayload,
                'payload_terkirim' => $cleanPayload,
            ];
        }

        $timeout = (int) (config('services.pragi_ai.timeout') ?: env('PRAGI_AI_ML_TIMEOUT', 10));
        $apiKey = config('services.pragi_ai.api_key') ?: env('PRAGI_AI_ML_KEY');

        try {
            $req = Http::timeout($timeout)->acceptJson()->asJson();
            if (! empty($apiKey)) {
                $req->withToken($apiKey)->withHeaders(['X-API-Key' => $apiKey]);
            }

            $response = $req->post($url, $cleanPayload);

            if ($response->successful()) {
                $pyData = $response->json() ?? [];

                // 1. Ekstrak field spesifik dari output API AI Python pengguna:
                // Format: {"confidence_percent": 62.62, "label": "Berisiko Penyakit Ginjal Kronis (CKD)", "prediction": 1, "probability": {"ckd": 0.6262, "no_ckd": 0.3738}, "status": "success"}
                $rawPrediction = $pyData['prediction'] ?? $pyData['prediksi'] ?? $pyData['result'] ?? null;
                $rawLabel = $pyData['label'] ?? null;
                $rawConfidence = $pyData['confidence_percent'] ?? $pyData['confidence'] ?? null;
                $rawProb = $pyData['probability'] ?? $pyData['proba'] ?? $pyData['probabilitas'] ?? null;
                $rawRisk = $pyData['kategori_risiko'] ?? $pyData['risk_category'] ?? $pyData['risk'] ?? null;

                // 2. Parsing probabilitas detail (CKD & No-CKD)
                $probCkd = null;
                $probNoCkd = null;
                $probabilityDetail = null;

                if (is_array($rawProb)) {
                    $probCkd = isset($rawProb['ckd']) ? (float) $rawProb['ckd'] : null;
                    $probNoCkd = isset($rawProb['no_ckd']) ? (float) $rawProb['no_ckd'] : null;
                    $probabilityDetail = [
                        'ckd' => $probCkd !== null ? round($probCkd, 4) : 0.0,
                        'no_ckd' => $probNoCkd !== null ? round($probNoCkd, 4) : 0.0,
                    ];
                } elseif (is_numeric($rawProb)) {
                    $val = (float) $rawProb;
                    $probCkd = ($val > 1.0) ? round($val / 100.0, 4) : round($val, 4);
                    $probNoCkd = round(1.0 - $probCkd, 4);
                    $probabilityDetail = [
                        'ckd' => $probCkd,
                        'no_ckd' => $probNoCkd,
                    ];
                }

                // 3. Parsing confidence percent
                $confidencePercent = null;
                if (! is_null($rawConfidence)) {
                    $confidencePercent = (float) $rawConfidence;
                } elseif ($probCkd !== null) {
                    $confidencePercent = round($probCkd * 100.0, 2);
                }

                // 4. Parsing prediction code (0 atau 1)
                $predictionCode = null;
                if (! is_null($rawPrediction)) {
                    if (is_numeric($rawPrediction)) {
                        $predictionCode = (int) $rawPrediction;
                    } else {
                        $pStr = strtolower(trim((string) $rawPrediction));
                        $predictionCode = in_array($pStr, ['1', 'yes', 'ya', 'true', 'ckd', 'positive', 'positif', 'berisiko'], true) ? 1 : 0;
                    }
                }

                // 5. Tentukan Kategori Risiko Klinis (rendah / sedang / tinggi)
                $kategoriRisiko = null;
                if (! empty($rawRisk)) {
                    $rStr = strtolower(trim((string) $rawRisk));
                    if (str_contains($rStr, 'tinggi') || str_contains($rStr, 'high')) {
                        $kategoriRisiko = 'tinggi';
                    } elseif (str_contains($rStr, 'sedang') || str_contains($rStr, 'medium') || str_contains($rStr, 'moderate')) {
                        $kategoriRisiko = 'sedang';
                    } else {
                        $kategoriRisiko = 'rendah';
                    }
                } elseif ($predictionCode !== null) {
                    if ($predictionCode === 1) {
                        // Jika model mendeteksi risiko CKD
                        if (($confidencePercent !== null && $confidencePercent >= 60.0) || ($probCkd !== null && $probCkd >= 0.60)) {
                            $kategoriRisiko = 'tinggi';
                        } else {
                            $kategoriRisiko = 'sedang';
                        }
                    } else {
                        // Jika model mendeteksi tidak berisiko (prediction = 0)
                        if ($probCkd !== null && $probCkd >= 0.35) {
                            $kategoriRisiko = 'sedang';
                        } else {
                            $kategoriRisiko = 'rendah';
                        }
                    }
                } elseif ($probCkd !== null) {
                    if ($probCkd >= 0.60) {
                        $kategoriRisiko = 'tinggi';
                    } elseif ($probCkd >= 0.30) {
                        $kategoriRisiko = 'sedang';
                    } else {
                        $kategoriRisiko = 'rendah';
                    }
                } else {
                    $kategoriRisiko = $internalEval['kategori_risiko'];
                }

                // 6. Tentukan Label / Hasil Prediksi (prioritaskan label AI Python)
                $prediksiText = $rawLabel ?? match ($kategoriRisiko) {
                    'tinggi' => 'Berisiko Penyakit Ginjal Kronis (CKD)',
                    'sedang' => 'Risiko Sedang Penyakit Ginjal Kronis (CKD)',
                    default => 'Tidak Berisiko Penyakit Ginjal Kronis (Stabil)',
                };

                // 7. Susun Rekomendasi Medis Personal Berdasarkan Model AI
                $confText = $confidencePercent !== null ? " (Tingkat Keyakinan AI: {$confidencePercent}%)" : '';
                $rekomendasi = $pyData['recommendation'] ?? $pyData['rekomendasi'] ?? match ($kategoriRisiko) {
                    'tinggi' => "PERINGATAN AI{$confText}: Model Machine Learning mendeteksi indikasi kuat {$prediksiText}. Segera jadwalkan konsultasi telemedisin dengan Dokter Ginjal (Nefrolog) di GIAT untuk pemeriksaan laboratorium ureum, kreatinin, dan laju filtrasi glomerulus (eGFR).",
                    'sedang' => "PERHATIAN AI{$confText}: Terdeteksi beberapa indikator risiko kardiovaskular dan penurunan fungsi ginjal. Jaga pola asupan cairan 2L/hari, batasi konsumsi garam & gula berlebih, catat kondisi fisik di menu Pantau, dan jadwalkan konsultasi berkala.",
                    default => "HASIL AI{$confText}: Profil klinis Anda saat ini dinilai stabil dan berisiko rendah terhadap CKD. Pertahankan gaya hidup sehat, cukupi hidrasi air putih harian, dan hindari penggunaan obat pereda nyeri (NSAID) bebas tanpa anjuran dokter.",
                };

                return [
                    'status' => 'success',
                    'sumber_prediksi' => 'python_ml_api',
                    'is_fallback' => false,
                    'status_transmisi' => 'transmitted_200',
                    'skor' => $internalEval['skor'],
                    'kategori_risiko' => $kategoriRisiko,
                    'hasil_prediksi' => $prediksiText,
                    'label' => $prediksiText,
                    'prediction' => $predictionCode ?? ($kategoriRisiko === 'tinggi' ? 1 : 0),
                    'confidence_percent' => $confidencePercent,
                    'probabilitas' => $probCkd ?? round($internalEval['skor'] / 15.0, 4),
                    'probability' => $probabilityDetail ?? [
                        'ckd' => $probCkd ?? round($internalEval['skor'] / 15.0, 4),
                        'no_ckd' => $probNoCkd ?? round(1.0 - ($internalEval['skor'] / 15.0), 4),
                    ],
                    'rekomendasi' => $rekomendasi,
                    'faktor_risiko_teridentifikasi' => $internalEval['faktor_risiko_teridentifikasi'],
                    'fitur_terencoded' => $encodedPayload,
                    'payload_terkirim' => $cleanPayload,
                    'raw_python_response' => $pyData,
                ];
            }

            // Jika API Python mengembalikan status HTTP 4xx atau 5xx
            Log::warning("Pragi AI Python API returned HTTP {$response->status()}: " . $response->body());

            $fallbackProbCkd = round($internalEval['skor'] / 15.0, 4);
            $fallbackProbNoCkd = round(1.0 - $fallbackProbCkd, 4);
            $fallbackConf = round($fallbackProbCkd * 100.0, 2);
            $fallbackPred = $internalEval['kategori_risiko'] === 'tinggi' ? 1 : 0;
            $fallbackLabel = $fallbackPred === 1 ? 'Berisiko Penyakit Ginjal Kronis (CKD)' : 'Tidak Berisiko Penyakit Ginjal Kronis (Stabil)';

            return [
                'status' => 'fallback',
                'sumber_prediksi' => 'internal_rule_engine',
                'is_fallback' => true,
                'status_transmisi' => 'http_error_' . $response->status(),
                'pesan_sistem' => "API Python mengembalikan HTTP {$response->status()}. Prediksi dialihkan ke Engine GIAT.",
                'skor' => $internalEval['skor'],
                'kategori_risiko' => $internalEval['kategori_risiko'],
                'hasil_prediksi' => $fallbackLabel,
                'label' => $fallbackLabel,
                'prediction' => $fallbackPred,
                'confidence_percent' => $fallbackConf,
                'probabilitas' => $fallbackProbCkd,
                'probability' => [
                    'ckd' => $fallbackProbCkd,
                    'no_ckd' => $fallbackProbNoCkd,
                ],
                'rekomendasi' => $internalEval['rekomendasi'],
                'faktor_risiko_teridentifikasi' => $internalEval['faktor_risiko_teridentifikasi'],
                'fitur_terencoded' => $encodedPayload,
                'payload_terkirim' => $cleanPayload,
            ];

        } catch (\Throwable $e) {
            Log::warning("Pragi AI Python API transmission exception: {$e->getMessage()}");

            $fallbackProbCkd = round($internalEval['skor'] / 15.0, 4);
            $fallbackProbNoCkd = round(1.0 - $fallbackProbCkd, 4);
            $fallbackConf = round($fallbackProbCkd * 100.0, 2);
            $fallbackPred = $internalEval['kategori_risiko'] === 'tinggi' ? 1 : 0;
            $fallbackLabel = $fallbackPred === 1 ? 'Berisiko Penyakit Ginjal Kronis (CKD)' : 'Tidak Berisiko Penyakit Ginjal Kronis (Stabil)';

            return [
                'status' => 'fallback',
                'sumber_prediksi' => 'internal_rule_engine',
                'is_fallback' => true,
                'status_transmisi' => 'connection_failed',
                'pesan_sistem' => "Gagal terhubung ke API Python ({$e->getMessage()}). Prediksi dialihkan ke Engine GIAT.",
                'skor' => $internalEval['skor'],
                'kategori_risiko' => $internalEval['kategori_risiko'],
                'hasil_prediksi' => $fallbackLabel,
                'label' => $fallbackLabel,
                'prediction' => $fallbackPred,
                'confidence_percent' => $fallbackConf,
                'probabilitas' => $fallbackProbCkd,
                'probability' => [
                    'ckd' => $fallbackProbCkd,
                    'no_ckd' => $fallbackProbNoCkd,
                ],
                'rekomendasi' => $internalEval['rekomendasi'],
                'faktor_risiko_teridentifikasi' => $internalEval['faktor_risiko_teridentifikasi'],
                'fitur_terencoded' => $encodedPayload,
                'payload_terkirim' => $cleanPayload,
            ];
        }
    }

    /**
     * Check health and connectivity to external Python ML API.
     */
    public static function checkPythonApiHealth(): array
    {
        $url = config('services.pragi_ai.url') ?: env('PRAGI_AI_ML_URL');
        if (empty($url)) {
            return [
                'configured' => false,
                'online' => false,
                'url' => null,
                'message' => 'PRAGI_AI_ML_URL belum diatur di .env',
            ];
        }

        try {
            $timeout = min((int) (config('services.pragi_ai.timeout') ?: 5), 5);
            $res = Http::timeout($timeout)->get($url);

            return [
                'configured' => true,
                'online' => $res->successful(),
                'status_code' => $res->status(),
                'url' => $url,
                'message' => $res->successful() ? 'API Python aktif dan terhubung' : "API merespon status {$res->status()}",
            ];
        } catch (\Throwable $e) {
            return [
                'configured' => true,
                'online' => false,
                'url' => $url,
                'error' => $e->getMessage(),
                'message' => 'Tidak dapat terhubung ke API Python: ' . $e->getMessage(),
            ];
        }
    }

    protected static function cleanBinary($val, string $default = 'No'): string
    {
        if (is_null($val)) return $default;
        $v = strtolower(trim((string) $val));
        if (in_array($v, ['yes', 'ya', '1', 'true', 'y'], true)) {
            return 'Yes';
        }
        if (in_array($v, ['no', 'tidak', '0', 'false', 'n'], true)) {
            return 'No';
        }
        return $default;
    }

    protected static function cleanSex($val, string $default = 'Female'): string
    {
        $v = strtolower(trim((string) $val));
        if (in_array($v, ['male', 'laki-laki', 'l', 'pria', '1'], true)) {
            return 'Male';
        }
        if (in_array($v, ['female', 'perempuan', 'p', 'wanita', '0'], true)) {
            return 'Female';
        }
        return $default;
    }

    protected static function cleanRace($val, string $default = 'White'): string
    {
        $allowed = [
            'american indian/alaskan native' => 'American Indian/Alaskan Native',
            'asian' => 'Asian',
            'black' => 'Black',
            'hispanic' => 'Hispanic',
            'other' => 'Other',
            'white' => 'White',
        ];
        $v = strtolower(trim((string) $val));
        return $allowed[$v] ?? $default;
    }

    protected static function cleanDiabetic($val, string $default = 'No'): string
    {
        $allowed = [
            'no' => 'No',
            'no, borderline diabetes' => 'No, borderline diabetes',
            'borderline' => 'No, borderline diabetes',
            'pradiabetes' => 'No, borderline diabetes',
            'yes' => 'Yes',
            'ya' => 'Yes',
            '1' => 'Yes',
            'yes (during pregnancy)' => 'Yes (during pregnancy)',
            'pregnancy' => 'Yes (during pregnancy)',
        ];
        $v = strtolower(trim((string) $val));
        return $allowed[$v] ?? $default;
    }

    protected static function cleanGenHealth($val, string $default = 'Good'): string
    {
        $allowed = [
            'poor' => 'Poor',
            'fair' => 'Fair',
            'good' => 'Good',
            'very good' => 'Very good',
            'excellent' => 'Excellent',
        ];
        $v = strtolower(trim((string) $val));
        return $allowed[$v] ?? $default;
    }

    protected static function cleanAgeCategory($val, string $default = '18-24'): string
    {
        $allowed = [
            '18-24', '25-29', '30-34', '35-39', '40-44',
            '45-49', '50-54', '55-59', '60-64', '65-69',
            '70-74', '75-79', '80 or older',
        ];
        $valStr = trim((string) $val);
        foreach ($allowed as $item) {
            if (strcasecmp($valStr, $item) === 0) {
                return $item;
            }
        }
        if (is_numeric($val) && (int) $val >= 0 && (int) $val <= 12) {
            return $allowed[(int) $val] ?? $default;
        }
        return $default;
    }
}

