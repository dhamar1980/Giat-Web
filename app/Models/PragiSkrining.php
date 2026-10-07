<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PragiSkrining extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pragi_skrining';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_pasien',
        'total_skor',
        'kategori_risiko',
        'rekomendasi',
        'tanggal_skrining',
        // Legacy fillable aliases
        'hasil_prediksi',
        'tanggal_screening',
    ];

    protected $casts = [
        'total_skor' => 'integer',
        'tanggal_skrining' => 'datetime',
    ];

    protected $appends = [
        'id_pragi',
        'hasil_prediksi',
        'tanggal_screening',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->tanggal_skrining)) {
                $model->tanggal_skrining = now();
            }
            if (empty($model->total_skor)) {
                $model->total_skor = 0;
            }
            if (empty($model->kategori_risiko)) {
                $model->kategori_risiko = 'rendah';
            }
        });
    }

    public function getIdPragiAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getHasilPrediksiAttribute(): ?string
    {
        $kat = $this->attributes['kategori_risiko'] ?? 'rendah';
        return 'Risiko ' . ucfirst($kat) . ' CKD';
    }

    public function setHasilPrediksiAttribute($value): void
    {
        $val = strtolower((string) $value);
        $this->attributes['kategori_risiko'] = match (true) {
            str_contains($val, 'tinggi') => 'tinggi',
            str_contains($val, 'sedang') => 'sedang',
            default => 'rendah',
        };
    }

    public function getTanggalScreeningAttribute()
    {
        return $this->attributes['tanggal_skrining'] ?? null;
    }

    public function setTanggalScreeningAttribute($value): void
    {
        $this->attributes['tanggal_skrining'] = $value;
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function jawabanDetail(): HasMany
    {
        return $this->hasMany(PragiJawabanDetail::class, 'id_skrining', 'id');
    }
}
