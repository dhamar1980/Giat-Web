<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PragiJawabanDetail extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pragi_jawaban_detail';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_skrining',
        'id_pertanyaan',
        'jawaban',
    ];

    public function skrining(): BelongsTo
    {
        return $this->belongsTo(PragiSkrining::class, 'id_skrining', 'id');
    }

    public function pertanyaan(): BelongsTo
    {
        return $this->belongsTo(PragiPertanyaan::class, 'id_pertanyaan', 'id');
    }
}
