<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PantauKesehatan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pantau_kesehatan';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_pasien',
        'berat_badan',
        'tinggi_badan',
        'bmi',
        'kategori_bmi',
        'kondisi',
        'keluhan',
        'detail_keluhan',
        'tanggal_pantau',
        // Legacy fillable aliases
        'bb',
        'tb',
        'imt',
    ];

    protected $casts = [
        'berat_badan' => 'float',
        'tinggi_badan' => 'float',
        'bmi' => 'float',
        'tanggal_pantau' => 'datetime',
    ];

    protected $appends = [
        'id_pantau',
        'bb',
        'tb',
        'imt',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->berat_badan) && !empty($model->attributes['bb'])) {
                $model->berat_badan = $model->attributes['bb'];
            }
            if (empty($model->tinggi_badan) && !empty($model->attributes['tb'])) {
                $model->tinggi_badan = $model->attributes['tb'];
            }
            if (empty($model->bmi) && !empty($model->attributes['imt'])) {
                $model->bmi = $model->attributes['imt'];
            }
            if (empty($model->tanggal_pantau)) {
                $model->tanggal_pantau = now();
            }
            if (empty($model->bmi) && !empty($model->berat_badan) && !empty($model->tinggi_badan)) {
                $tbMeter = $model->tinggi_badan > 3 ? ($model->tinggi_badan / 100) : $model->tinggi_badan;
                $model->bmi = round($model->berat_badan / ($tbMeter * $tbMeter), 2);
            }
            if (empty($model->kategori_bmi) && !empty($model->bmi)) {
                $model->kategori_bmi = match (true) {
                    $model->bmi < 18.5 => 'kurang',
                    $model->bmi <= 24.9 => 'normal',
                    $model->bmi <= 29.9 => 'berlebih',
                    default => 'obesitas',
                };
            }
            if (empty($model->kondisi)) {
                $model->kondisi = match ($model->kategori_bmi ?? 'normal') {
                    'normal' => 'baik',
                    'berlebih', 'kurang' => 'perlu_perhatian',
                    default => 'waspada',
                };
            }
            unset($model->attributes['bb'], $model->attributes['tb'], $model->attributes['imt']);
        });

        static::saving(function ($model) {
            unset($model->attributes['bb'], $model->attributes['tb'], $model->attributes['imt']);
        });
    }

    public function getIdPantauAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getBbAttribute(): ?float
    {
        return isset($this->attributes['berat_badan']) ? (float) $this->attributes['berat_badan'] : null;
    }

    public function setBbAttribute($value): void
    {
        $this->attributes['berat_badan'] = $value;
    }

    public function getTbAttribute(): ?float
    {
        return isset($this->attributes['tinggi_badan']) ? (float) $this->attributes['tinggi_badan'] : null;
    }

    public function setTbAttribute($value): void
    {
        $this->attributes['tinggi_badan'] = $value;
    }

    public function getImtAttribute(): ?float
    {
        return isset($this->attributes['bmi']) ? (float) $this->attributes['bmi'] : null;
    }

    public function setImtAttribute($value): void
    {
        $this->attributes['bmi'] = $value;
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }
}
