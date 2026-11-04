<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ============================================================================
 * MODEL: USER (PENGGUNA MULTI-ROLE, PROFIL LENGKAP & PENGUNJUNG TERKLASIFIKASI)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan entitas tabel `users` beserta atribut profil lengkap:
 *   - Pemilik Toko (`seller`): Siswa (`nis_nisn`, `class_name`, `major_name`) &
 *     Guru (`nuptk`, `is_teacher`). Satu akun hanya memiliki satu toko (`hasOne`).
 *   - Pengunjung/Pembeli (`buyer`):
 *     1. `pelajar`: `nis_nisn`, `school_origin`, `class_name`, alamat, `phone`
 *     2. `keluarga`: `nik`, `family_student_name`, `family_student_class`, `family_student_major`, alamat, `phone`
 *     3. `alumni`: `nik`, `graduation_year`, alamat, `phone`
 *     4. `umum`: `nik`, alamat, `phone`
 *
 * Algoritma:
 *   1. `getFullAddressAttribute()`: Menggabungkan komponen alamat terstruktur
 *      (`address_detail`, `desa`, `kecamatan`, `kabupaten`, `kode_pos`) dengan
 *      filtering elemen kosong (`array_filter`).
 *   2. `getSubtypeLabelAttribute()`: Pattern Matching (`match`) untuk menghasilkan
 *      label kategori pengguna/pengunjung pada laporan Admin.
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
        'user_subtype',
        'is_teacher',
        'nis_nisn',
        'nuptk',
        'nik',
        'class_name',
        'major_name',
        'school_origin',
        'family_student_name',
        'family_student_class',
        'family_student_major',
        'graduation_year',
        'desa',
        'kecamatan',
        'kabupaten',
        'kode_pos',
        'address_detail',
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
            'is_teacher'        => 'boolean',
        ];
    }

    /**
     * Fungsi: Relasi 1-ke-1 (Satu Toko Satu Akun) untuk Pemilik Toko (Siswa/Guru).
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProductComment::class);
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    /**
     * Fungsi: Menyusun teks alamat lengkap dari Desa, Kecamatan, Kabupaten, dan Kode Pos.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_detail,
            $this->desa ? "Desa/Kel. {$this->desa}" : null,
            $this->kecamatan ? "Kec. {$this->kecamatan}" : null,
            $this->kabupaten ? "Kab/Kota {$this->kabupaten}" : null,
            $this->kode_pos ? "Kode Pos {$this->kode_pos}" : null,
        ]);

        return ! empty($parts) ? implode(', ', $parts) : '-';
    }

    /**
     * Fungsi: Menghasilkan label kategori pengunjung/pemilik toko yang mudah dibaca.
     */
    public function getSubtypeLabelAttribute(): string
    {
        if ($this->is_teacher || $this->user_subtype === 'guru') {
            return $this->role === 'admin' ? 'Guru (Admin Sekolah)' : 'Guru SMK Plus Al Hilal';
        }

        return match ($this->user_subtype) {
            'siswa'    => 'Siswa SMK Plus Al Hilal',
            'jurusan'  => 'Unit Produksi Jurusan',
            'pelajar'  => 'Pengunjung: Pelajar',
            'keluarga' => 'Pengunjung: Keluarga SMK Plus Al Hilal',
            'alumni'   => 'Pengunjung: Alumni',
            'umum'     => 'Pengunjung Umum',
            default    => match ($this->role) {
                'super_admin' => 'Super Admin',
                'admin'       => 'Admin Sekolah',
                'seller'      => 'Pemilik Toko',
                default       => 'Pengunjung Umum',
            },
        };
    }
}
