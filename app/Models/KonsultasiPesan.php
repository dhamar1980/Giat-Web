<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsultasiPesan extends Model
{
    use HasFactory;

    protected $table = 'konsultasi_pesan';
    protected $primaryKey = 'id_pesan';

    protected $fillable = [
        'id_konsultasi',
        'sender_type',
        'sender_id',
        'pesan',
        'tipe',
        'attachment',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id_konsultasi');
    }

    /**
     * Get sender model (Pasien or Dokter).
     */
    public function getSenderAttribute()
    {
        if ($this->sender_type === 'dokter') {
            return Dokter::find($this->sender_id);
        }
        return Pasien::find($this->sender_id);
    }
}
