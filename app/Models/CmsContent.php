<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * ELOQUENT MODEL: CMS CONTENT & MANAJEMEN IKLAN MITRA (SUPER ADMIN)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `cms_contents` yang menyimpan:
 *   1. Elemen konten teks & tautan website (`topbar`, `hero`, `values`, `footer`).
 *   2. Baris Iklan Mitra Kerjasama Sekolah (`section = 'iklan'`, seperti
 *      Bank Syariah Indonesia / BSI, PT. Mega Mentari, dan Daihatsu) lengkap
 *      dengan pengaturan durasi tampil per slide (`duration_seconds`) serta
 *      masa berlaku kontrak (`contract_start` s/d `contract_end`).
 *
 * Algoritma:
 *   1. `val($key, $default)`: Memuat seluruh baris `cms_contents` ke dalam
 *      memori per-request. Jika item dinonaktifkan (`is_active = false`),
 *      mengembalikan string kosong; jika aktif, mengembalikan `content`.
 *   2. `activePartnerAds()`: Menyaring item dengan `section = 'iklan'`,
 *      `is_active = true`, serta memeriksa masa kontrak (`contract_start <= today`
 *      dan `contract_end >= today`) agar iklan yang masa kontraknya habis
 *      otomatis berhenti tayang di halaman depan.
 * ============================================================================
 */
class CmsContent extends Model
{
    public const DEFAULT_KEYS = [
        'topbar_left',
        'topbar_right',
        'navbar_subtitle',
        'hero_badge',
        'hero_title',
        'hero_subtitle',
        'hero_cta_text',
        'hero_stats_text',
        'value_1',
        'value_2',
        'value_3',
        'story_badge',
        'story_title',
        'story_description',
        'footer_description',
        'footer_instagram',
        'footer_whatsapp',
        'footer_email',
        'policy_discount_pelajar',
        'policy_discount_keluarga',
        'policy_discount_alumni',
        'policy_discount_umum',
        'policy_discount_bazar',
    ];

    protected $fillable = [
        'key',
        'section',
        'title',
        'partner_name',
        'badge_text',
        'content',
        'image',
        'link_url',
        'duration_seconds',
        'contract_start',
        'contract_end',
        'theme_color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'sort_order'       => 'integer',
            'duration_seconds' => 'integer',
            'contract_start'   => 'date',
            'contract_end'     => 'date',
        ];
    }

    /**
     * Cache koleksi model CmsContent yang diindeks berdasarkan `key`.
     *
     * @var Collection<string, CmsContent>|null
     */
    private static ?Collection $cachedItems = null;

    /**
     * Memuat seluruh baris `cms_contents` satu kali per siklus request.
     *
     * @return Collection<string, CmsContent>
     */
    public static function allKeyed(): Collection
    {
        if (self::$cachedItems === null) {
            if (! Schema::hasTable('cms_contents')) {
                self::$cachedItems = collect();
            } else {
                self::$cachedItems = static::orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->keyBy('key');
            }
        }

        return self::$cachedItems;
    }

    /**
     * Mengambil teks konten CMS berdasarkan `key`.
     */
    public static function val(string $key, string $default = ''): string
    {
        $item = static::allKeyed()->get($key);

        if (! $item) {
            return $default;
        }

        if (! $item->is_active) {
            return '';
        }

        return ($item->content !== null && $item->content !== '')
            ? (string) $item->content
            : $default;
    }

    /**
     * Memeriksa apakah kontrak iklan masih berlaku pada hari ini.
     */
    public function isWithinContract(): bool
    {
        $today = Carbon::today();

        if ($this->contract_start && $today->lt(Carbon::parse($this->contract_start)->startOfDay())) {
            return false;
        }

        if ($this->contract_end && $today->gt(Carbon::parse($this->contract_end)->endOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Menghasilkan label status masa kontrak iklan untuk Dashboard Super Admin.
     */
    public function getContractStatusLabelAttribute(): string
    {
        if (! $this->contract_start && ! $this->contract_end) {
            return 'Tanpa Batas Kontrak';
        }

        $today = Carbon::today();

        if ($this->contract_start && $today->lt(Carbon::parse($this->contract_start)->startOfDay())) {
            return 'Terjadwal (' . Carbon::parse($this->contract_start)->format('d/m/Y') . ')';
        }

        if ($this->contract_end) {
            $end = Carbon::parse($this->contract_end)->endOfDay();
            if ($today->gt($end)) {
                return 'Kontrak Berakhir (' . $end->format('d/m/Y') . ')';
            }

            $daysLeft = (int) $today->diffInDays(Carbon::parse($this->contract_end)->startOfDay(), false);
            return 'Aktif • Sisa ' . max(1, $daysLeft) . ' hari (s/d ' . $end->format('d/m/Y') . ')';
        }

        return 'Aktif sejak ' . Carbon::parse($this->contract_start)->format('d/m/Y');
    }

    /**
     * Mengambil daftar Iklan Mitra (`section = 'iklan'`) yang aktif dan masih
     * berada dalam rentang masa kontrak untuk ditayangkan bergantian di bagian atas beranda.
     *
     * @return Collection<int, CmsContent>
     */
    public static function activePartnerAds(): Collection
    {
        return static::allKeyed()
            ->filter(fn (CmsContent $item) => $item->section === 'iklan' && $item->is_active && $item->isWithinContract())
            ->values();
    }

    /**
     * Mengambil daftar banner pengumuman tambahan di luar kunci bawaan dan di luar iklan.
     *
     * @return Collection<int, CmsContent>
     */
    public static function customItems(): Collection
    {
        return static::allKeyed()
            ->filter(fn (CmsContent $item) => $item->is_active
                && $item->section !== 'iklan'
                && $item->section !== 'discounts'
                && ! str_starts_with($item->key, 'policy_discount_')
                && ! in_array($item->key, self::DEFAULT_KEYS, true))
            ->values();
    }

    public static function clearCache(): void
    {
        self::$cachedItems = null;
    }
}
