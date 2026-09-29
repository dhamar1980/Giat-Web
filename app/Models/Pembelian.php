<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian';
    protected $primaryKey = 'id_pembelian';

    protected $fillable = [
        'id_pasien',
        'id_obat',
        'id_resep',
        'id_apotek',
        'tipe_pembelian',
        'status_pembayaran',
        'status_pesanan',
        'total_harga',
        'jumlah',
        'alamat_pengiriman',
        'catatan',
        'nomor_resi',
        'kurir',
        'status_lacak',
        'tanggal_pembelian',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pembelian' => 'datetime',
            'total_harga' => 'decimal:2',
            'jumlah' => 'integer',
            'status_lacak' => 'array',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }

    public function resepObat(): BelongsTo
    {
        return $this->belongsTo(ResepObat::class, 'id_resep', 'id_resep');
    }
}
