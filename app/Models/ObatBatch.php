<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObatBatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'obat_batches';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_stock',
        'id_obat',
        'id_apotek',
        'id_apoteker',
        'no_batch',
        'stok_batch',
        'jumlah_stock',
        'tanggal_kadaluwarsa',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->no_batch)) {
                $model->no_batch = 'BATCH-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
            if (empty($model->tanggal_kadaluwarsa)) {
                $model->tanggal_kadaluwarsa = now()->addYears(2);
            }
        });
    }

    protected $casts = [
        'stok_batch' => 'integer',
        'tanggal_kadaluwarsa' => 'date',
    ];

    protected $appends = [
        'id_stock',
        'id_apoteker',
        'jumlah_stock',
    ];

    public function getIdStockAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getIdApotekerAttribute(): ?string
    {
        return $this->attributes['id_apotek'] ?? null;
    }

    public function setIdApotekerAttribute($value): void
    {
        $this->attributes['id_apotek'] = $value;
    }

    public function getJumlahStockAttribute(): ?int
    {
        return isset($this->attributes['stok_batch']) ? (int) $this->attributes['stok_batch'] : null;
    }

    public function setJumlahStockAttribute($value): void
    {
        $this->attributes['stok_batch'] = $value;
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }
}
