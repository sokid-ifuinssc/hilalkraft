<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ============================================================================
 * MODEL: USER (PENGGUNA MULTI-ROLE & AUDIT KREDENSIAL SUPER ADMIN)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan entitas tabel `users` menggunakan pola Active Record
 *   Eloquent ORM, mengatur keamanan atribusi kolom (`$fillable`), hashing
 *   otomatis pada `password`, penyimpanan `password_plain` untuk visibilitas
 *   real-time Super Admin, serta relasi ke Toko dan Pesanan.
 *
 * Algoritma:
 *   1. Dual-Credential Sync: Saat password dibuat atau diubah, kolom `password`
 *      di-hash menggunakan algoritma Bcrypt satu arah untuk verifikasi login,
 *      sementara `password_plain` menyimpan nilai aktualnya agar Super Admin
 *      dapat memantau kredensial baik sebelum maupun sesudah diedit.
 *   2. Role Checking (`hasRole`): Menggunakan algoritma pencarian himpunan
 *      `in_array(..., true)` (Strict Type Comparison O(k)).
 * ============================================================================
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'password_plain',
        'role',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'password_plain',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }
}
