<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Konsultasi extends Model
{
    use HasFactory, HasUuids;

    public function newEloquentBuilder($query): Builder
    {
        return new class($query) extends Builder {
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if (is_array($column)) {
                    $remapped = [];
                    foreach ($column as $key => $val) {
                        $newKey = ($key === 'status_konsultasi') ? 'status' : $key;
                        $remapped[$newKey] = $val;
                    }
                    return parent::where($remapped, $operator, $value, $boolean);
                }

                if ($column === 'status_konsultasi') {
                    $column = 'status';
                }
                return parent::where($column, $operator, $value, $boolean);
            }
        };
    }

    protected $table = 'konsultasi';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'id_pasien',
        'id_dokter',
        'tanggal_konsultasi',
        'jam_mulai',
        'jam_selesai',
        'keluhan_awal',
        'isi_konsultasi',
        'jenis_layanan',
        'status',
        'status_konsultasi',
    ];

    protected $casts = [
        'tanggal_konsultasi' => 'date',
    ];

    protected $appends = [
        'id_konsultasi',
        'status_konsultasi',
        'status_pembayaran',
        'biaya',
        'room_id',
        'catatan_dokter',
        'isi_konsultasi',
    ];

    public function getIdKonsultasiAttribute(): ?string
    {
        return $this->attributes['id'] ?? null;
    }

    public function getStatusKonsultasiAttribute(): ?string
    {
        return $this->attributes['status'] ?? null;
    }

    public function setStatusKonsultasiAttribute($value): void
    {
        $this->attributes['status'] = $value;
    }

    public function getIsiKonsultasiAttribute(): ?string
    {
        return $this->attributes['keluhan_awal'] ?? null;
    }

    public function setIsiKonsultasiAttribute($value): void
    {
        $this->attributes['keluhan_awal'] = $value;
    }

    public function getStatusPembayaranAttribute(): ?string
    {
        return $this->pembayaran?->status_bayar ?? 'menunggu_pembayaran';
    }

    public function getRoomIdAttribute(): ?string
    {
        return $this->videoSession?->room_id ?? null;
    }

    public function getCatatanDokterAttribute(): ?string
    {
        return $this->resep?->catatan_dokter ?? null;
    }

    public function getBiayaAttribute(): ?float
    {
        return $this->pembayaran?->jumlah_bayar ?? ($this->dokter?->tarif_konsultasi ?? 50000);
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'id_pasien', 'id_pasien');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'id_dokter', 'id_dokter');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(KonsultasiPembayaran::class, 'id_konsultasi', 'id');
    }

    public function videoSession(): HasOne
    {
        return $this->hasOne(KonsultasiVideoSession::class, 'id_konsultasi', 'id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(KonsultasiPesan::class, 'id_konsultasi', 'id');
    }

    public function resep(): HasOne
    {
        return $this->hasOne(Resep::class, 'id_konsultasi', 'id');
    }

    public function resepObat(): HasOne
    {
        return $this->hasOne(Resep::class, 'id_konsultasi', 'id');
    }
}
