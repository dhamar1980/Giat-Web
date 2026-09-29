<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResepObat extends Model
{
    use HasFactory;

    protected $table = 'resep_obat';
    protected $primaryKey = 'id_resep';

    protected $fillable = [
        'id_dokter',
        'id_pasien',
        'id_obat',
        'id_apoteker',
        'dosis',
        'tanggal_resep',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_resep' => 'date',
        ];
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apoteker', 'id_apotek');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_resep', 'id_resep');
    }
}
