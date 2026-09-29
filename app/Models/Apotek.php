<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Apotek extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'apotek';
    protected $primaryKey = 'id_apotek';

    protected $fillable = [
        'nama',
        'email',
        'firebase_uid',
        'auth_provider',
        'password',
        'no_sip',
        'jam_operasional',
        'lokasi_apotek',
        'area_layanan',
        'status_layanan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function stockObat(): HasMany
    {
        return $this->hasMany(StockObat::class, 'id_apoteker', 'id_apotek');
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(ResepObat::class, 'id_apoteker', 'id_apotek');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_apotek', 'id_apotek');
    }
}
