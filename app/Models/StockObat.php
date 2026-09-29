<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockObat extends Model
{
    use HasFactory;

    protected $table = 'stock_obat';
    protected $primaryKey = 'id_stock';

    protected $fillable = [
        'id_obat',
        'id_apoteker',
        'jumlah_stock',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_stock' => 'integer',
        ];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apoteker', 'id_apotek');
    }
}
