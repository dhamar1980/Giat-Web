<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdukasiKesehatan extends Model
{
    use HasFactory;

    protected $table = 'edukasi_kesehatan';
    protected $primaryKey = 'id_edukasi';

    protected $fillable = [
        'judul',
        'isi_edukasi',
        'kategori',
        'gambar',
        'tanggal_publish',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_publish' => 'datetime',
        ];
    }

    public function dokter(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Dokter::class, 'dokter_edukasi', 'id_edukasi', 'id_dokter');
    }

    public function pantau(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Pantau::class, 'pantau_edukasi', 'id_edukasi', 'id_pantau');
    }
}
