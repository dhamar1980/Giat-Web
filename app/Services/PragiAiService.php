<?php

namespace App\Services;

use App\Models\PragiPertanyaan;
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
}
