<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pasien extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_hp',
        'alamat',
        'jenis_kelamin',
        'tanggal_lahir',
        'gol_darah',
        'NIK',
        'foto_profile',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'password' => 'hashed',
        ];
    }

    public function konsultasi(): HasMany
    {
        return $this->hasMany(Konsultasi::class, 'id_pasien', 'id_pasien');
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(ResepObat::class, 'id_pasien', 'id_pasien');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_pasien', 'id_pasien');
    }

    public function pragi(): HasMany
    {
        return $this->hasMany(Pragi::class, 'id_pasien', 'id_pasien');
    }

    public function reminder(): HasMany
    {
        return $this->hasMany(Reminder::class, 'id_pasien', 'id_pasien');
    }

    public function pantau(): HasMany
    {
        return $this->hasMany(Pantau::class, 'id_pasien', 'id_pasien');
    }
}
