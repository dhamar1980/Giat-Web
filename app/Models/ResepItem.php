<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResepItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'resep_item';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_resep',
        'nama_obat',
        'dosis',
        'aturan_pakai',
        'waktu_penggunaan',
        'jumlah',
        'catatan_khusus',
    ];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'id_resep', 'id');
    }
}
