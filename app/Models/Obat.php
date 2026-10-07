<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'obat';
    protected $primaryKey = 'id_obat';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_obat',
        'id_apotek',
        'nama_obat',
        'kategori',
        'bentuk_sediaan',
        'dosis',
        'satuan_kemasan',
        'stok_total',
        'harga_beli',
        'harga_jual',
        'aturan_pakai_umum',
        'foto_obat',
    ];

    protected $casts = [
        'stok_total' => 'integer',
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
    ];

    protected $appends = [
        'harga',
        'stok',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id_obat)) {
                $model->id_obat = (string) Str::uuid();
            }
        });
    }

    public function getHargaAttribute(): ?float
    {
        return (float) ($this->attributes['harga_jual'] ?? 0);
    }

    public function setHargaAttribute($value): void
    {
        $this->attributes['harga_jual'] = $value;
    }

    public function getStokAttribute(): ?int
    {
        return (int) ($this->attributes['stok_total'] ?? 0);
    }

    public function setStokAttribute($value): void
    {
        $this->attributes['stok_total'] = $value;
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ObatBatch::class, 'id_obat', 'id_obat');
    }

    public function stockObat(): HasMany
    {
        return $this->hasMany(ObatBatch::class, 'id_obat', 'id_obat');
    }

    public function pesananItems(): HasMany
    {
        return $this->hasMany(PesananObatItem::class, 'id_obat', 'id_obat');
    }
}
