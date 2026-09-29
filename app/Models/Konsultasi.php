<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Konsultasi extends Model
{
    use HasFactory;

    protected $table = 'konsultasi';
    protected $primaryKey = 'id_konsultasi';

    protected $fillable = [
        'id_pasien',
        'id_dokter',
        'tanggal_konsultasi',
        'status_konsultasi',
        'status_pembayaran',
        'biaya',
        'room_id',
        'waktu_mulai',
        'waktu_selesai',
        'catatan_dokter',
        'isi_konsultasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_konsultasi' => 'datetime',
            'waktu_mulai' => 'datetime',
            'waktu_selesai' => 'datetime',
            'biaya' => 'decimal:2',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    public function pesan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KonsultasiPesan::class, 'id_konsultasi', 'id_konsultasi')->orderBy('created_at', 'asc');
    }
}
