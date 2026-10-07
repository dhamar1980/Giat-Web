<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resep extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'resep';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'no_resep',
        'id_konsultasi',
        'id_dokter',
        'id_pasien',
        'tanggal_resep',
        'diagnosis',
        'catatan_dokter',
        'status',
    ];

    protected $casts = [
        'tanggal_resep' => 'date',
    ];

    protected $appends = [
        'id_resep',
        'catatan',
    ];

    public function getIdResepAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getCatatanAttribute(): ?string
    {
        return $this->attributes['catatan_dokter'] ?? null;
    }

    public function setCatatanAttribute($value): void
    {
        $this->attributes['catatan_dokter'] = $value;
    }

    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->no_resep)) {
                $model->no_resep = 'RXP-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'id_resep', 'id');
    }

    public function obat(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'id_resep', 'id');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_dokter', 'id_apotek')->withDefault();
    }
}
