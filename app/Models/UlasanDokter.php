<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UlasanDokter extends Model
{
    use HasFactory;

    protected $table = 'ulasan_dokter';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_dokter',
        'id_pasien',
        'nama_pasien',
        'id_konsultasi',
        'rating',
        'ulasan',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke model Dokter
     */
    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    /**
     * Relasi ke model Pasien (nullable)
     */
    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    /**
     * Relasi ke model Konsultasi (nullable)
     */
    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id');
    }
}
