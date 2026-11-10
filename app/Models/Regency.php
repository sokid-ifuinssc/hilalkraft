<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ============================================================================
 * MODEL: REGENCY (MASTER KABUPATEN & KOTA SE-INDONESIA)
 * ============================================================================
 * Fungsi:
 *   Menyediakan master data 514 Kabupaten dan Kota di seluruh Indonesia
 *   untuk dropdown formulir pendaftaran, profil, dan filter pencarian admin.
 * ============================================================================
 */
class Regency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'province',
        'type',
    ];

    /**
     * Mengambil daftar seluruh Kabupaten & Kota dikelompokkan per Provinsi,
     * dengan Provinsi Jawa Barat di urutan pertama (Wilayah Utama SMK Plus Al Hilal).
     *
     * @return Collection
     */
    public static function getGroupedByProvince(): Collection
    {
        return static::orderByRaw("CASE WHEN province = 'Jawa Barat' THEN 0 WHEN province = 'DKI Jakarta' THEN 1 WHEN province = 'Banten' THEN 2 WHEN province = 'Jawa Tengah' THEN 3 WHEN province = 'DI Yogyakarta' THEN 4 WHEN province = 'Jawa Timur' THEN 5 ELSE 6 END")
            ->orderBy('province')
            ->orderBy('name')
            ->get()
            ->groupBy('province');
    }

    /**
     * Mengambil list flat nama Kabupaten & Kota untuk dropdown filter admin.
     *
     * @return Collection
     */
    public static function getFlatList(): Collection
    {
        return static::orderByRaw("CASE WHEN name LIKE '%Cirebon%' THEN 0 WHEN province = 'Jawa Barat' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->pluck('name');
    }
}
