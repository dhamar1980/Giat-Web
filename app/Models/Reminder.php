<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reminder extends Model
{
    use HasFactory;

    protected $table = 'reminder';
    protected $primaryKey = 'id_reminder';

    protected $fillable = [
        'id_pasien',
        'nama',
        'tanggal',
        'waktu',
        'keterangan',
        'pengulangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function pantau(): HasMany
    {
        return $this->hasMany(Pantau::class, 'id_reminder', 'id_reminder');
    }
}
