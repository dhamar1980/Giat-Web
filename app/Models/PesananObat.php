<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesananObat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pesanan_obat';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'no_pesanan',
        'id_pasien',
        'id_apotek',
        'id_resep',
        'tipe_pesanan',
        'metode_pengambilan',
        'nama_penerima',
        'no_hp_penerima',
        'alamat_pengiriman',
        'subtotal',
        'ongkos_kirim',
        'total_bayar',
        'metode_pembayaran',
        'status_pembayaran',
        'progress_step',
        'status_pesanan',
        'alasan_penolakan',
        // Legacy aliases
        'tipe_pembelian',
        'total_harga',
        'id_pembelian',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->no_pesanan)) {
                $model->no_pesanan = 'ORD-' . strtoupper(\Illuminate\Support\Str::random(10));
            }
            if (empty($model->nama_penerima)) {
                $model->nama_penerima = 'Pasien';
            }
            if (empty($model->no_hp_penerima)) {
                $model->no_hp_penerima = '-';
            }
            if (empty($model->tipe_pesanan) && !empty($model->attributes['tipe_pembelian'])) {
                $model->tipe_pesanan = $model->attributes['tipe_pembelian'];
            }
            if (empty($model->total_bayar) && !empty($model->attributes['total_harga'])) {
                $model->total_bayar = $model->attributes['total_harga'];
                if (empty($model->subtotal)) {
                    $model->subtotal = $model->attributes['total_harga'];
                }
            }
            unset($model->attributes['tipe_pembelian'], $model->attributes['total_harga'], $model->attributes['id_pembelian']);
        });

        static::saving(function ($model) {
            unset($model->attributes['tipe_pembelian'], $model->attributes['total_harga'], $model->attributes['id_pembelian']);
        });
    }

    protected $casts = [
        'subtotal' => 'decimal:2',
        'ongkos_kirim' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'progress_step' => 'integer',
    ];

    protected $appends = [
        'id_pembelian',
        'total_harga',
        'tipe_pembelian',
    ];

    public function getIdPembelianAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getTotalHargaAttribute(): ?float
    {
        return isset($this->attributes['total_bayar']) ? (float) $this->attributes['total_bayar'] : null;
    }

    public function setTotalHargaAttribute($value): void
    {
        $this->attributes['total_bayar'] = $value;
    }

    public function getTipePembelianAttribute(): ?string
    {
        return $this->attributes['tipe_pesanan'] ?? null;
    }

    public function setTipePembelianAttribute($value): void
    {
        $this->attributes['tipe_pesanan'] = $value;
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'id_resep', 'id');
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(PesananObatTracking::class, 'id_pesanan', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PesananObatItem::class, 'id_pesanan', 'id');
    }
}
