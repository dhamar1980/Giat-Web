<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonsultasiPesan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'konsultasi_pesan';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_konsultasi',
        'id_sender',
        'pesan',
        'attachment_url',
        'is_read',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'id_pesan',
        'sender_id',
        'attachment',
    ];

    public function getIdPesanAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getSenderIdAttribute(): ?string
    {
        return $this->attributes['id_sender'] ?? null;
    }

    public function setSenderIdAttribute($value): void
    {
        $this->attributes['id_sender'] = $value;
    }

    public function getAttachmentAttribute(): ?string
    {
        return $this->attributes['attachment_url'] ?? null;
    }

    public function setAttachmentAttribute($value): void
    {
        $this->attributes['attachment_url'] = $value;
    }

    public function konsultasi(): BelongsTo
    {
        return $this->belongsTo(Konsultasi::class, 'id_konsultasi', 'id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_sender', 'id');
    }
}
