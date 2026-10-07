<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdukasiArtikel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'edukasi_artikel';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'judul',
        'kategori',
        'konten',
        'gambar_url',
        'penulis',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'id_edukasi',
        'gambar',
        'isi_edukasi',
        'tanggal_publish',
    ];

    public function getIdEdukasiAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getGambarAttribute(): ?string
    {
        return $this->attributes['gambar_url'] ?? null;
    }

    public function setGambarAttribute($value): void
    {
        $this->attributes['gambar_url'] = $value;
    }

    public function getIsiEdukasiAttribute(): ?string
    {
        return $this->attributes['konten'] ?? null;
    }

    public function setIsiEdukasiAttribute($value): void
    {
        $this->attributes['konten'] = $value;
    }

    public function getTanggalPublishAttribute(): ?string
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function setTanggalPublishAttribute($value): void
    {
        $this->attributes['created_at'] = $value;
    }
}
