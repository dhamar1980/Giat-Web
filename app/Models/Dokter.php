<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Dokter extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'dokter';
    protected $primaryKey = 'id_dokter';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_hp',
        'no_sip',
        'no_str',
        'spesialisasi',
        'biaya_konsultasi',
        'institusi',
        'jenis_kelamin',
        'alamat_praktik',
        'foto_profil',
        'notifikasi_settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'biaya_konsultasi' => 'decimal:2',
            'notifikasi_settings' => 'array',
        ];
    }

    public function konsultasi(): HasMany
    {
        return $this->hasMany(Konsultasi::class, 'id_dokter', 'id_dokter');
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(ResepObat::class, 'id_dokter', 'id_dokter');
    }

    public function pantau(): HasMany
    {
        return $this->hasMany(Pantau::class, 'id_dokter', 'id_dokter');
    }

    public function edukasiKesehatan(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(EdukasiKesehatan::class, 'dokter_edukasi', 'id_dokter', 'id_edukasi');
    }
}
