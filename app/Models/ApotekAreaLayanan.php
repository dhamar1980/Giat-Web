<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekAreaLayanan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'apotek_area_layanan';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_apotek',
        'nama_area',
        'detail',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }
}
