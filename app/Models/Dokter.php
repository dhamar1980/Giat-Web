<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Dokter extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'dokter';
    protected $primaryKey = 'id_dokter';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_dokter',
        'nama',
        'spesialisasi',
        'institusi',
        'no_str',
        'no_sip',
        'no_hp',
        'tarif_konsultasi',
        'foto_profile',
        'bio',
    ];

    protected $appends = [
        'email',
        'firebase_uid',
        'auth_provider',
        'biaya_konsultasi',
        'foto_profil',
        'institusi',
        'alamat_praktik',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id_dokter)) {
                $model->id_dokter = (string) Str::uuid();
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
            'tarif_konsultasi' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_dokter', 'id');
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

    public function getBiayaKonsultasiAttribute(): ?float
    {
        return (float) ($this->attributes['tarif_konsultasi'] ?? 0);
    }

    public function setBiayaKonsultasiAttribute($value): void
    {
        $this->attributes['tarif_konsultasi'] = $value;
    }

    public function getFotoProfilAttribute(): ?string
    {
        return $this->attributes['foto_profile'] ?? null;
    }

    public function setFotoProfilAttribute($value): void
    {
        $this->attributes['foto_profile'] = $value;
    }

    public function getInstitusiAttribute(): ?string
    {
        return $this->attributes['instansi'] ?? null;
    }

    public function setInstitusiAttribute($value): void
    {
        $this->attributes['instansi'] = $value;
    }

    public function getAlamatPraktikAttribute(): ?string
    {
        return $this->attributes['instansi'] ?? null;
    }

    public function setAlamatPraktikAttribute($value): void
    {
        $this->attributes['instansi'] = $value;
    }

    public function konsultasi(): HasMany
    {
        return $this->hasMany(Konsultasi::class, 'id_dokter', 'id_dokter');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_dokter', 'id_dokter');
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_dokter', 'id_dokter');
    }
}
