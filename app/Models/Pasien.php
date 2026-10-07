<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Pasien extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_pasien',
        'nama',
        'no_hp',
        'alamat',
        'jenis_kelamin',
        'nik',
        'tanggal_lahir',
        'golongan_darah',
        'foto_profile',
    ];

    protected $appends = [
        'email',
        'firebase_uid',
        'auth_provider',
        'NIK',
        'gol_darah',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->id_pasien)) {
                $model->id_pasien = (string) Str::uuid();
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
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pasien', 'id');
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

    public function getNIKAttribute(): ?string
    {
        return $this->attributes['nik'] ?? null;
    }

    public function setNIKAttribute($value): void
    {
        $this->attributes['nik'] = $value;
    }

    public function getGolDarahAttribute(): ?string
    {
        return $this->attributes['golongan_darah'] ?? null;
    }

    public function setGolDarahAttribute($value): void
    {
        $this->attributes['golongan_darah'] = $value;
    }

    public function getFotoProfilAttribute(): ?string
    {
        return $this->attributes['foto_profile'] ?? null;
    }

    public function setFotoProfilAttribute($value): void
    {
        $this->attributes['foto_profile'] = $value;
    }

    public function konsultasi(): HasMany
    {
        return $this->hasMany(Konsultasi::class, 'id_pasien', 'id_pasien');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_pasien', 'id_pasien');
    }

    public function resepObat(): HasMany
    {
        return $this->hasMany(Resep::class, 'id_pasien', 'id_pasien');
    }

    public function pesananObat(): HasMany
    {
        return $this->hasMany(PesananObat::class, 'id_pasien', 'id_pasien');
    }

    public function pembelian(): HasMany
    {
        return $this->hasMany(PesananObat::class, 'id_pasien', 'id_pasien');
    }

    public function pragiSkrining(): HasMany
    {
        return $this->hasMany(PragiSkrining::class, 'id_pasien', 'id_pasien');
    }

    public function pragi(): HasMany
    {
        return $this->hasMany(PragiSkrining::class, 'id_pasien', 'id_pasien');
    }

    public function pragiChats(): HasMany
    {
        return $this->hasMany(PragiChat::class, 'id_pasien', 'id_pasien');
    }

    public function pantauKesehatan(): HasMany
    {
        return $this->hasMany(PantauKesehatan::class, 'id_pasien', 'id_pasien');
    }

    public function pantau(): HasMany
    {
        return $this->hasMany(PantauKesehatan::class, 'id_pasien', 'id_pasien');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'id_pasien', 'id_pasien');
    }
}
