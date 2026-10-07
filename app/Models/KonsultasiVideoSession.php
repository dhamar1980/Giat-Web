<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsultasiVideoSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'konsultasi_video_session';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_konsultasi',
        'room_id',
        'token',
        'durasi_menit',
        'status_panggilan',
    ];

    protected $casts = [
        'durasi_menit' => 'integer',
    ];

    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id');
    }
}
