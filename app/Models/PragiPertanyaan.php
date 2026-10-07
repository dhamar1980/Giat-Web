<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PragiPertanyaan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pragi_pertanyaan';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'pertanyaan',
        'kategori',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function jawabanDetail(): HasMany
    {
        return $this->hasMany(PragiJawabanDetail::class, 'id_pertanyaan', 'id');
    }
}
