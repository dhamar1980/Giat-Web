<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApotekJamOperasional extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'apotek_jam_operasional';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_apotek',
        'hari',
        'is_open',
        'jam_buka',
        'jam_tutup',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function apotek(): BelongsTo
    {
        return $this->belongsTo(Apotek::class, 'id_apotek', 'id_apotek');
    }
}
