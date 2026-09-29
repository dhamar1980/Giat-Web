<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'obat';
    protected $primaryKey = 'id_obat';

    protected $fillable = [
        'nama_obat',
        'harga',
        'kategori',
        'tipe_obat',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(ResepObat::class, 'id_obat', 'id_obat');
    }

    public function stockObat(): HasMany
    {
        return $this->hasMany(StockObat::class, 'id_obat', 'id_obat');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_obat', 'id_obat');
    }
}
