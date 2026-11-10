<?php

namespace Database\Seeders;

use App\Models\BazaarEvent;
use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\CmsContent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductLike;
use App\Models\Store;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ============================================================================
 * SEEDER: DATABASE SEEDER BERSIH (HANYA SUPER ADMIN & ADMIN)
 * ============================================================================
 * Sesuai instruksi:
 *   1. Seluruh data seeder contoh (pembeli, toko siswa, produk karya, pesanan,
 *      event bazar contoh, dan iklan dummy) TELAH DIHAPUS.
 *   2. HANYA MENYISAKAN akun Super Admin dan Admin (Guru SMK Plus Al Hilal):
 *      - Super Admin: superadmin@hilalkraft.sch.id
 *      - Admin Guru: silvi@hilalkraft.sch.id (Ibu Silvi Dwi Manitik, S.Pd)
 *      - Admin Guru: melinda@hilalkraft.sch.id (Ibu Melinda, SE)
 *   3. Mempertahankan konfigurasi teks layout dasar CMS website (topbar, hero, footer)
 *      agar tampilan antarmuka beranda tetap rapi dan siap digunakan secara riil.
 * ============================================================================
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Menyimpan snapshot akun Super Admin & Admin serta teks CMS terkini dari database
     * ke `storage/app/live_seed_snapshot.json` agar perubahan password atau kontak
     * yang dilakukan Super Admin tetap tersimpan aman.
     */
    public static function syncLiveSnapshotToStorage(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            $snapshotPath = storage_path('app/live_seed_snapshot.json');

            // HANYA simpan akun Super Admin & Admin (Guru)
            $users = User::whereIn('role', ['super_admin', 'admin'])
                ->get()
                ->keyBy('email')
                ->map(function (User $u) {
                    return [
                        'name'           => $u->name,
                        'email'          => $u->email,
                        'password_plain' => $u->password_plain ?: 'password123',
                        'role'           => $u->role,
                        'user_subtype'   => $u->user_subtype,
                        'is_teacher'     => (bool) $u->is_teacher,
                        'nis_nisn'       => $u->nis_nisn,
                        'nuptk'          => $u->nuptk,
                        'nik'            => $u->nik,
                        'class_name'     => $u->class_name,
                        'major_name'     => $u->major_name,
                        'school_origin'  => $u->school_origin,
                        'phone'          => $u->phone,
                        'desa'           => $u->desa,
                        'kecamatan'      => $u->kecamatan,
                        'kabupaten'      => $u->kabupaten,
                        'kode_pos'       => $u->kode_pos,
                        'address_detail' => $u->address_detail,
                        'is_active'      => (bool) $u->is_active,
                    ];
                })->toArray();

            $cms = [];
            if (Schema::hasTable('cms_contents')) {
                $cms = CmsContent::orderBy('sort_order')
                    ->where('section', '!=', 'iklan')
                    ->get()
                    ->keyBy('key')
                    ->map(function (CmsContent $c) {
                        return [
                            'key'              => $c->key,
                            'section'          => $c->section,
                            'title'            => $c->title,
                            'partner_name'     => $c->partner_name,
                            'badge_text'       => $c->badge_text,
                            'content'          => $c->content,
                            'link_url'         => $c->link_url,
                            'image'            => $c->image,
                            'duration_seconds' => $c->duration_seconds,
                            'contract_start'   => $c->contract_start,
                            'contract_end'     => $c->contract_end,
                            'theme_color'      => $c->theme_color,
                            'is_active'        => (bool) $c->is_active,
                            'sort_order'       => $c->sort_order,
                        ];
                    })->toArray();
            }

            $payload = [
                'updated_at'   => date('c'),
                'users'        => $users,
                'cms_contents' => $cms,
            ];

            @file_put_contents($snapshotPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
            // Abaikan kegagalan file write jika folder storage belum memiliki izin tulis
        }
    }

    public function run(): void
    {
        $snapshotUsers = [];
        $snapshotCms = [];

        $snapshotPath = storage_path('app/live_seed_snapshot.json');
        if (is_file($snapshotPath)) {
            $rawSnapshot = json_decode((string) file_get_contents($snapshotPath), true) ?: [];
            $snapshotUsers = $rawSnapshot['users'] ?? [];
            $snapshotCms = $rawSnapshot['cms_contents'] ?? [];
        }

        // =====================================================================
        // 1. PEMBERSIHAN DATA SEEDER DUMMY / CONTOH
        // =====================================================================

        // 1a. Hapus interaksi dummy (komentar, suka, log dilihat)
        if (Schema::hasTable('product_comments')) {
            ProductComment::whereHas('product', function ($q) {
                $q->whereIn('slug', [
                    'gantungan-kunci-bunga',
                    'lampu-meja-kayu-sora',
                    'kaligrafi-kayu-ayat-kursi-ukir',
                    'lampu-meja-bambu-anyam-minimalis',
                    'brownies-lumer-kurma-al-hilal',
                    'gantungan-kunci-karakter-iyung-iyung',
                    'gantungan-kunci-karakter-5k-yulianti',
                ]);
            })->orWhereIn('comment', [
                'Karya ganci bunganya cantik dan rapi, bahan kawat bulunya lembut dan kuat!',
                'Pelayanan cepat dan ramah via WA. Sukses terus untuk karya siswa SMK PAL!',
                'Bagus sekali untuk suvenir!',
            ])->delete();
        }

        if (Schema::hasTable('product_likes')) {
            ProductLike::whereHas('product', function ($q) {
                $q->whereIn('slug', [
                    'gantungan-kunci-bunga',
                    'lampu-meja-kayu-sora',
                    'kaligrafi-kayu-ayat-kursi-ukir',
                    'lampu-meja-bambu-anyam-minimalis',
                    'brownies-lumer-kurma-al-hilal',
                    'gantungan-kunci-karakter-iyung-iyung',
                    'gantungan-kunci-karakter-5k-yulianti',
                ]);
            })->delete();
        }

        if (Schema::hasTable('visitor_logs')) {
            VisitorLog::whereHas('product', function ($q) {
                $q->whereIn('slug', [
                    'gantungan-kunci-bunga',
                    'lampu-meja-kayu-sora',
                    'kaligrafi-kayu-ayat-kursi-ukir',
                    'lampu-meja-bambu-anyam-minimalis',
                    'brownies-lumer-kurma-al-hilal',
                    'gantungan-kunci-karakter-iyung-iyung',
                    'gantungan-kunci-karakter-5k-yulianti',
                ]);
            })->delete();
        }

        // 1b. Hapus pesanan dummy & item pesanan
        if (Schema::hasTable('order_items')) {
            OrderItem::whereHas('order', function ($q) {
                $q->whereIn('invoice_number', [
                    'HK-20261104-001',
                    'HK-20261103-RPFAA',
                    'HK-20261028-003',
                    'HK-20261015-004',
                ]);
            })->delete();
        }

        if (Schema::hasTable('orders')) {
            Order::whereIn('invoice_number', [
                'HK-20261104-001',
                'HK-20261103-RPFAA',
                'HK-20261028-003',
                'HK-20261015-004',
            ])->delete();
        }

        // 1c. Hapus produk dummy
        if (Schema::hasTable('products')) {
            Product::whereIn('slug', [
                'gantungan-kunci-bunga',
                'lampu-meja-kayu-sora',
                'kaligrafi-kayu-ayat-kursi-ukir',
                'lampu-meja-bambu-anyam-minimalis',
                'brownies-lumer-kurma-al-hilal',
                'gantungan-kunci-karakter-iyung-iyung',
                'gantungan-kunci-karakter-5k-yulianti',
            ])->delete();
        }

        // 1d. Hapus toko dummy
        if (Schema::hasTable('stores')) {
            Store::withTrashed()->whereIn('slug', [
                'toko-khoiriyah-muntazah',
                'tokoganci-iyung-iyung',
                'studio-karya-rizky',
                'galeri-kriya-fauzan',
                'karya-inovasi-pak-hendra',
                'tokoganci-iyung-yulianti',
            ])->forceDelete();
        }

        // 1e. Hapus akun dummy seeder (pembeli & siswa contoh)
        if (Schema::hasTable('users')) {
            User::whereIn('email', [
                'pembeli@hilalkraft.sch.id',
                'khoiriyah.siswa@hilalkraft.sch.id',
                'yulianti.siswa@hilalkraft.sch.id',
                'admin@hilalkraft.sch.id',
                'fauzan.siswa@hilalkraft.sch.id',
                'hendra.guru@hilalkraft.sch.id',
                'nisa.pembeli@gmail.com',
                'rizky.siswa@hilalkraft.sch.id',
            ])->delete();
        }

        // 1f. Hapus event bazar contoh
        if (Schema::hasTable('bazaar_events')) {
            BazaarEvent::whereIn('voucher_code', ['BAZARSMKPAL'])->delete();
        }

        // 1g. Hapus iklan mitra dummy jika ada
        if (Schema::hasTable('cms_contents')) {
            CmsContent::whereIn('key', ['iklan_bsi', 'iklan_mega_mentari', 'iklan_daihatsu'])->delete();
        }

        // 1h. Hapus seeder kategori & kelas lama agar murni dikelola Super Admin / Admin
        if (Schema::hasTable('categories')) {
            Category::whereIn('slug', [
                'kerajinan',
                'teknologi',
                'tata-boga',
                'seni',
                'fesyen',
                'kerajinan-tangan',
                'kuliner-olahan-pangan',
                'desain-grafis-cetak',
                'busana-tekstil',
                'teknologi-iot-siswa',
                'seni-kaligrafi-islami',
            ])->delete();
        }

        if (Schema::hasTable('class_majors')) {
            ClassMajor::query()->delete();
        }

        // =====================================================================
        // 2. SEED AKUN UTAMA: HANYA SUPER ADMIN & ADMIN (GURU SMK PLUS AL HILAL)
        // =====================================================================
        $coreAdmins = [
            'superadmin@hilalkraft.sch.id' => [
                'name'           => 'Super Admin SMK Plus Al Hilal',
                'password_plain' => 'password123',
                'role'           => 'super_admin',
                'phone'          => '081234567890',
                'desa'           => 'Tegalgubug',
                'kecamatan'      => 'Arjawinangun',
                'kabupaten'      => 'Cirebon',
                'kode_pos'       => '45162',
                'address_detail' => 'Jl. H. Manshur No. 07, Desa Tegalgubug, Kec. Arjawinangun, Kab. Cirebon',
                'is_active'      => true,
            ],
            'silvi@hilalkraft.sch.id' => [
                'name'           => 'Ibu Silvi Dwi Manitik, S.Pd',
                'password_plain' => 'password123',
                'role'           => 'admin',
                'user_subtype'   => 'guru',
                'is_teacher'     => true,
                'nuptk'          => '0000000000000001',
                'phone'          => '083845906618',
                'desa'           => 'Gegesik Kidul',
                'kecamatan'      => 'Gegesik',
                'kabupaten'      => 'Cirebon',
                'kode_pos'       => '45164',
                'address_detail' => 'Blok 2 RT 002/003, Desa Gegesik Kidul, Kec. Gegesik, Kab. Cirebon',
                'is_active'      => true,
            ],
            'melinda@hilalkraft.sch.id' => [
                'name'           => 'Ibu Melinda, SE',
                'password_plain' => 'password123',
                'role'           => 'admin',
                'user_subtype'   => 'guru',
                'is_teacher'     => true,
                'nuptk'          => '0000000000000014',
                'phone'          => '081234567891',
                'desa'           => 'Tegalgubug',
                'kecamatan'      => 'Arjawinangun',
                'kabupaten'      => 'Cirebon',
                'kode_pos'       => '45162',
                'address_detail' => 'Desa Tegalgubug, Kec. Arjawinangun, Kab. Cirebon 45162',
                'is_active'      => true,
            ],
        ];

        foreach ($coreAdmins as $email => $defaultAttrs) {
            $attrs = array_merge($defaultAttrs, $snapshotUsers[$email] ?? []);
            $plainPass = $attrs['password_plain'] ?? 'password123';
            $attrs['password'] = Hash::make($plainPass);
            unset($attrs['email']);

            User::updateOrCreate(
                ['email' => $email],
                $attrs
            );
        }

        // =====================================================================
        // 3. TEKS CMS DEFAULT WEBSITE (TOPBAR, HERO, FOOTER)
        // =====================================================================
        if (Schema::hasTable('cms_contents')) {
            $defaultCmsItems = [
                [
                    'key'        => 'topbar_left',
                    'section'    => 'topbar',
                    'title'      => 'Slogan Topbar Kiri',
                    'content'    => 'Dari ruang kelas, untuk kehidupan sehari-hari.',
                    'sort_order' => 10,
                ],
                [
                    'key'        => 'topbar_right',
                    'section'    => 'topbar',
                    'title'      => 'Label Topbar Kanan',
                    'content'    => 'Unit Resmi SMK Plus Al Hilal • Lembaga Inkubator Bisnis & Wirausaha Muda SMK PAL',
                    'sort_order' => 11,
                ],
                [
                    'key'        => 'navbar_subtitle',
                    'section'    => 'topbar',
                    'title'      => 'Subjudul Lembaga (Footer)',
                    'content'    => 'Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL',
                    'sort_order' => 12,
                ],
                [
                    'key'        => 'hero_badge',
                    'section'    => 'hero',
                    'title'      => 'Badge Kecil Hero Beranda',
                    'content'    => 'HILALKRAF — KARYA SISWA BERKAH BERSAMA',
                    'sort_order' => 13,
                ],
                [
                    'key'        => 'hero_title',
                    'section'    => 'hero',
                    'title'      => 'Judul Utama Hero Beranda',
                    'content'    => 'Karya Inovatif Siswa SMK Plus Al Hilal',
                    'sort_order' => 14,
                ],
                [
                    'key'        => 'hero_subtitle',
                    'section'    => 'hero',
                    'title'      => 'Deskripsi Hero Beranda',
                    'content'    => 'Platform resmi hilalkraf milik SMK Plus Al Hilal di bawah pengelolaan Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL.',
                    'sort_order' => 15,
                ],
                [
                    'key'        => 'hero_cta_text',
                    'section'    => 'hero',
                    'title'      => 'Teks Tombol Utama Hero',
                    'content'    => 'Jelajahi Produk',
                    'sort_order' => 16,
                ],
                [
                    'key'        => 'value_1',
                    'section'    => 'values',
                    'title'      => 'Poin Keunggulan 1',
                    'content'    => 'Karya siswa asli',
                    'sort_order' => 17,
                ],
                [
                    'key'        => 'value_2',
                    'section'    => 'values',
                    'title'      => 'Poin Keunggulan 2',
                    'content'    => 'Langsung ke kreator',
                    'sort_order' => 18,
                ],
                [
                    'key'        => 'value_3',
                    'section'    => 'values',
                    'title'      => 'Poin Keunggulan 3',
                    'content'    => 'Dukung talenta muda',
                    'sort_order' => 19,
                ],
                [
                    'key'        => 'footer_description',
                    'section'    => 'footer',
                    'title'      => 'Deskripsi Footer',
                    'content'    => 'Platform resmi hilalkraf milik SMK Plus Al Hilal di bawah pengelolaan Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL. Belanja bermakna, dukung talenta wirausaha muda.',
                    'sort_order' => 20,
                ],
                [
                    'key'        => 'footer_instagram',
                    'section'    => 'footer',
                    'title'      => 'Instagram Footer',
                    'content'    => '@hilalkraf.smkpal',
                    'sort_order' => 21,
                ],
                [
                    'key'        => 'footer_whatsapp',
                    'section'    => 'footer',
                    'title'      => 'WhatsApp Footer',
                    'content'    => '+62 821-2938-0141',
                    'sort_order' => 22,
                ],
                [
                    'key'        => 'footer_email',
                    'section'    => 'footer',
                    'title'      => 'Email Footer',
                    'content'    => 'inkubator@hilalkraf.sch.id',
                    'sort_order' => 23,
                ],
            ];

            foreach ($defaultCmsItems as $item) {
                $mergedCms = array_merge($item, ['is_active' => true], $snapshotCms[$item['key']] ?? []);
                CmsContent::firstOrCreate(
                    ['key' => $item['key']],
                    $mergedCms
                );
            }

            CmsContent::whereIn('key', ['story_badge', 'story_title', 'story_description', 'hero_stats_text'])->delete();
            CmsContent::clearCache();
        }

        self::syncLiveSnapshotToStorage();
    }
}
