<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Apotek extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'apotek';
    protected $primaryKey = 'id_apotek';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_apotek',
        'nama_apotek',
        'penanggung_jawab',
        'no_sipa_sia',
        'no_hp',
        'alamat',
        'lokasi_lat_long',
        'status_layanan',
        'foto_profile',
    ];

    protected $appends = [
        'email',
        'firebase_uid',
        'auth_provider',
        'nama',
        'no_sip',
        'lokasi_apotek',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id_apotek)) {
                $model->id_apotek = (string) Str::uuid();
            }
        });

        static::saving(function ($model) {
            unset(
                $model->attributes['email'],
                $model->attributes['password'],
                $model->attributes['firebase_uid'],
                $model->attributes['auth_provider']
            );
        });
    }

    protected function casts(): array
    {
        return [
            'status_layanan' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_apotek', 'id');
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email ?? ($this->getKey() ? User::where('id', $this->getKey())->value('email') : null);
    }

    public function setEmailAttribute($value): void
    {
        unset($this->attributes['email']);
        $id = $this->getKey();
        if ($id) {
            User::where('id', $id)->update(['email' => $value]);
        }
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->user?->password ?? ($this->getKey() ? User::where('id', $this->getKey())->value('password') : null);
    }

    public function setPasswordAttribute($value): void
    {
        unset($this->attributes['password']);
        $id = $this->getKey();
        if ($id) {
            User::where('id', $id)->update(['password' => $value]);
        }
    }

    public function getFirebaseUidAttribute(): ?string
    {
        return $this->user?->firebase_uid ?? ($this->getKey() ? User::where('id', $this->getKey())->value('firebase_uid') : null);
    }

    public function setFirebaseUidAttribute($value): void
    {
        unset($this->attributes['firebase_uid']);
        $id = $this->getKey();
        if ($id) {
            User::where('id', $id)->update(['firebase_uid' => $value]);
        }
    }

    public function getAuthProviderAttribute(): ?string
    {
        return $this->user?->auth_provider ?? ($this->getKey() ? User::where('id', $this->getKey())->value('auth_provider') : 'local');
    }

    public function setAuthProviderAttribute($value): void
    {
        unset($this->attributes['auth_provider']);
        $id = $this->getKey();
        if ($id) {
            User::where('id', $id)->update(['auth_provider' => $value]);
        }
    }

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['nama_apotek'] ?? null;
    }

    public function setNamaAttribute($value): void
    {
        $this->attributes['nama_apotek'] = $value;
    }

    public function getNoSipAttribute(): ?string
    {
        return $this->attributes['no_sipa_sia'] ?? null;
    }

    public function setNoSipAttribute($value): void
    {
        $this->attributes['no_sipa_sia'] = $value;
    }

    public function getLokasiApotekAttribute(): ?string
    {
        return $this->attributes['lokasi_lat_long'] ?? null;
    }

    public function setLokasiApotekAttribute($value): void
    {
        $this->attributes['lokasi_lat_long'] = $value;
    }

    public function jamOperasional(): HasMany
    {
        return $this->hasMany(ApotekJamOperasional::class, 'id_apotek', 'id_apotek');
    }

    public function areaLayanan(): HasMany
    {
        return $this->hasMany(ApotekAreaLayanan::class, 'id_apotek', 'id_apotek');
    }

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'id_apotek', 'id_apotek');
    }

    public function obatBatches(): HasMany
    {
        return $this->hasMany(ObatBatch::class, 'id_apotek', 'id_apotek');
    }

    public function stockObat(): HasMany
    {
        return $this->hasMany(ObatBatch::class, 'id_apotek', 'id_apotek');
    }

    public function pesananObat(): HasMany
    {
        return $this->hasMany(PesananObat::class, 'id_apotek', 'id_apotek');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(PesananObat::class, 'id_apotek', 'id_apotek');
    }
}
