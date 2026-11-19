<?php

namespace Database\Seeders;

use App\Models\CmsContent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * SEEDER: DATABASE SEEDER BERSIH & NON-DESTRUKTIF
 * ============================================================================
 * Seeder aman & idempoten (`firstOrCreate`):
 *   - Tidak pernah menghapus data toko, produk, kelas/jurusan, kategori,
 *     pesanan, maupun acara bazar yang sedang berjalan.
 *   - Menjamin akun inti Super Admin & Admin tersedia jika belum ada.
 *   - Menjamin konfigurasi default teks CMS & kebijakan diskon/poin bazar
 *     tersedia tanpa menimpa perubahan yang sudah disimpan Admin.
 * ============================================================================
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. AKUN INTI: SUPER ADMIN & ADMIN (GURU SMK PLUS AL HILAL)
        // =====================================================================
        $coreAdmins = [
            'superadmin@hilalkraft.sch.id' => [
                'name'           => 'Super Admin SMK Plus Al Hilal',
                'password'       => Hash::make('password123'),
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
                'password'       => Hash::make('password123'),
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
                'password'       => Hash::make('password123'),
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

        foreach ($coreAdmins as $email => $attrs) {
            User::firstOrCreate(
                ['email' => $email],
                $attrs
            );
        }

        // =====================================================================
        // 2. TEKS CMS DEFAULT & KEBIJAKAN STANDAR DISKON / POIN BAZAR
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
                [
                    'key'        => 'policy_bazaar_points_checkin',
                    'section'    => 'bazaar_policy',
                    'title'      => 'Default Poin Scan Hadir Acara Bazar',
                    'content'    => '50',
                    'sort_order' => 30,
                ],
                [
                    'key'        => 'policy_bazaar_points_stand_visit',
                    'section'    => 'bazaar_policy',
                    'title'      => 'Default Poin Scan Kunjungan Stand Bazar',
                    'content'    => '20',
                    'sort_order' => 31,
                ],
                [
                    'key'        => 'policy_bazaar_points_transaction',
                    'section'    => 'bazaar_policy',
                    'title'      => 'Default Poin Transaksi Bazar',
                    'content'    => '30',
                    'sort_order' => 32,
                ],
            ];

            foreach ($defaultCmsItems as $item) {
                CmsContent::firstOrCreate(
                    ['key' => $item['key']],
                    array_merge($item, ['type' => 'text', 'is_active' => true])
                );
            }

            CmsContent::clearCache();
        }
    }
}
