<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pantau extends Model
{
    use HasFactory;

    protected $table = 'pantau';
    protected $primaryKey = 'id_pantau';

    protected $fillable = [
        'id_pasien',
        'id_dokter',
        'id_reminder',
        'imt',
        'bb',
        'tb',
        'keluhan',
        'detail_keluhan',
        'kondisi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'imt' => 'decimal:2',
            'bb' => 'decimal:2',
            'tb' => 'decimal:2',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class, 'id_reminder', 'id_reminder');
    }

    public function edukasiKesehatan(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(EdukasiKesehatan::class, 'pantau_edukasi', 'id_pantau', 'id_edukasi');
    }
}
