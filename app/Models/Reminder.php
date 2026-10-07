<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'reminders';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_pasien',
        'tipe',
        'judul',
        'subjudul',
        'waktu',
        'tanggal',
        'pengulangan',
        'status',
        'is_active',
        // Legacy aliases
        'nama',
        'nama_obat',
        'keterangan',
        'dosis',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->tipe)) {
                $model->tipe = 'obat';
            }
            if (empty($model->judul) && !empty($model->attributes['nama'])) {
                $model->judul = $model->attributes['nama'];
            }
            if (empty($model->subjudul) && !empty($model->attributes['keterangan'])) {
                $model->subjudul = $model->attributes['keterangan'];
            }
            if (empty($model->status)) {
                $model->status = 'aktif';
            }
            unset($model->attributes['nama'], $model->attributes['keterangan'], $model->attributes['nama_obat'], $model->attributes['dosis']);
        });

        static::saving(function ($model) {
            if (empty($model->tipe)) {
                $model->tipe = 'obat';
            }
            if (empty($model->judul) && !empty($model->attributes['nama'])) {
                $model->judul = $model->attributes['nama'];
            }
            if (empty($model->subjudul) && !empty($model->attributes['keterangan'])) {
                $model->subjudul = $model->attributes['keterangan'];
            }
            if (empty($model->status)) {
                $model->status = 'aktif';
            }
            unset($model->attributes['nama'], $model->attributes['keterangan'], $model->attributes['nama_obat'], $model->attributes['dosis']);
        });
    }

    protected $appends = [
        'id_reminder',
        'nama',
        'nama_obat',
        'keterangan',
        'dosis',
    ];

    public function getIdReminderAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['judul'] ?? null;
    }

    public function setNamaAttribute($value): void
    {
        $this->attributes['judul'] = $value;
        unset($this->attributes['nama']);
    }

    public function getNamaObatAttribute(): ?string
    {
        return $this->attributes['judul'] ?? null;
    }

    public function setNamaObatAttribute($value): void
    {
        $this->attributes['judul'] = $value;
        unset($this->attributes['nama_obat']);
    }

    public function getKeteranganAttribute(): ?string
    {
        return $this->attributes['subjudul'] ?? null;
    }

    public function setKeteranganAttribute($value): void
    {
        $this->attributes['subjudul'] = $value;
        unset($this->attributes['keterangan']);
    }

    public function getDosisAttribute(): ?string
    {
        return $this->attributes['subjudul'] ?? null;
    }

    public function setDosisAttribute($value): void
    {
        $this->attributes['subjudul'] = $value;
        unset($this->attributes['dosis']);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }
}
