<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pragi extends Model
{
    use HasFactory;

    protected $table = 'pragi';
    protected $primaryKey = 'id_pragi';

    protected $fillable = [
        'id_pasien',
        'pertanyaan',
        'hasil_prediksi',
        'rekomendasi',
        'tanggal_screening',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_screening' => 'datetime',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }
}
