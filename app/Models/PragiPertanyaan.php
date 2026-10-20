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
        'kode_field',
        'pertanyaan',
        'pertanyaan_en',
        'kategori',
        'tipe_input',
        'opsi_jawaban',
        'urutan',
        'bobot',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
        'bobot' => 'integer',
        'opsi_jawaban' => 'array',
    ];

    public function jawabanDetail(): HasMany
    {
        return $this->hasMany(PragiJawabanDetail::class, 'id_pertanyaan', 'id');
    }
}
