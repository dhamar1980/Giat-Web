<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifikasi';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_user',
        'judul',
        'pesan',
        'kategori',
        'is_read',
        'created_at',
        // Legacy aliases
        'role',
        'tipe',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->kategori) && !empty($model->attributes['tipe'])) {
                $model->kategori = $model->attributes['tipe'];
            }
            if (empty($model->kategori)) {
                $model->kategori = 'info';
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
            unset($model->attributes['role'], $model->attributes['tipe']);
        });

        static::saving(function ($model) {
            unset($model->attributes['role'], $model->attributes['tipe']);
        });
    }

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'tipe',
    ];

    public function getTipeAttribute(): ?string
    {
        return $this->attributes['kategori'] ?? null;
    }

    public function setTipeAttribute($value): void
    {
        $this->attributes['kategori'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
