<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * MODEL: ACTIVITY_LOG (LOG SISTEM AKSES & AKTIVITAS LENGKAP SEPERTI HILALEDU)
 * ============================================================================
 * Fungsi:
 *   Mencatat dan melacak setiap pengguna yang masuk (login/logout/register)
 *   serta melakukan akses/perubahan data pada platform hilalkraft (Super Admin,
 *   Admin Guru, Siswa Pemilik Toko, Pembeli, maupun Tamu/Guest).
 * ============================================================================
 */
class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'activity_type',
        'title',
        'description',
        'ip_address',
        'device',
        'user_agent',
        'url',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper Utama: Mencatat Aktivitas Sistem secara Ringkas & Otomatis.
     *
     * @param string $activityType (login, logout, register, store_create, store_update, store_delete, store_restore, product_create, product_update, product_delete, order_update, cms_update, access)
     * @param string $title Judul singkat aktivitas
     * @param string|null $description Rincian aktivitas yang dilakukan
     * @param User|null $user Objek user yang bersangkutan (opsional, default: auth user)
     * @param array|null $properties Data tambahan dalam format key-value
     */
    public static function record(
        string $activityType,
        string $title,
        ?string $description = null,
        ?User $user = null,
        ?array $properties = null
    ): self {
        $request = request();
        $targetUser = $user ?? ($request ? $request->user() : auth()->user());

        $ip = $request ? ($request->header('CF-Connecting-IP') ?? $request->header('X-Forwarded-For') ?? $request->ip()) : null;
        if ($ip && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $userAgent = $request ? $request->userAgent() : null;
        $device = self::parseDevice($userAgent);

        return self::create([
            'user_id'       => $targetUser?->id,
            'user_name'     => $targetUser ? $targetUser->name : 'Pengunjung Tamu (Guest)',
            'user_role'     => $targetUser ? $targetUser->role : 'guest',
            'activity_type' => $activityType,
            'title'         => $title,
            'description'   => $description,
            'ip_address'    => $ip,
            'device'        => $device,
            'user_agent'    => $userAgent,
            'url'           => $request ? Str::limit($request->fullUrl(), 500, '') : null,
            'properties'    => $properties,
        ]);
    }

    /**
     * Parser Sederhana untuk Mendeteksi Perangkat & Browser (Windows, Android, iOS, Chrome, Safari, dll).
     */
    public static function parseDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Tidak Diketahui';
        }

        $os = 'Desktop / Lainnya';
        if (stripos($userAgent, 'Android') !== false) {
            $os = 'Android Mobile';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            $os = 'iOS / iPhone';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            $os = 'Windows PC';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
        }

        return "{$os} ({$browser})";
    }

    /**
     * Badge visual untuk tipe aktivitas di tampilan.
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->activity_type) {
            'login'          => ['label' => 'Login Masuk',        'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'logout'         => ['label' => 'Logout Keluar',      'color' => 'bg-slate-100 text-slate-700 border-slate-300'],
            'register'       => ['label' => 'Daftar Akun',        'color' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
            'store_create'   => ['label' => 'Buat Toko',          'color' => 'bg-teal-100 text-teal-800 border-teal-300'],
            'store_update'   => ['label' => 'Edit Toko',          'color' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'store_delete'   => ['label' => 'Hapus Toko',         'color' => 'bg-rose-100 text-rose-800 border-rose-300'],
            'store_restore'  => ['label' => 'Pulihkan Toko',      'color' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'product_create' => ['label' => 'Tambah Produk',      'color' => 'bg-purple-100 text-purple-800 border-purple-300'],
            'product_update' => ['label' => 'Edit Produk',        'color' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'product_delete' => ['label' => 'Hapus Produk',       'color' => 'bg-rose-100 text-rose-800 border-rose-300'],
            'order_update'   => ['label' => 'Progres Pesanan',    'color' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'cms_update'     => ['label' => 'Kelola CMS',         'color' => 'bg-sky-100 text-sky-800 border-sky-300'],
            default          => ['label' => 'Akses Halaman',      'color' => 'bg-gray-100 text-gray-700 border-gray-300'],
        };
    }
}

