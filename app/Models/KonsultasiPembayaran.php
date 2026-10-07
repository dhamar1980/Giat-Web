<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsultasiPembayaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'konsultasi_pembayaran';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_konsultasi',
        'no_invoice',
        'jumlah_bayar',
        'metode_pembayaran',
        'va_number',
        'status_bayar',
        'waktu_bayar',
    ];

    protected $casts = [
        'jumlah_bayar' => 'decimal:2',
        'waktu_bayar' => 'datetime',
    ];

    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id');
    }
}
