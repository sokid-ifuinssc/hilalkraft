<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\CmsContent;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ============================================================================
 * SEEDER: DATABASE SEEDER MINIMAL & RESMI SMK PLUS AL HILAL (HILALKRAF)
 * ============================================================================
 * Fungsi:
 *   Menyiapkan data esensial platform `hilalkraf` sesuai instruksi terbaru:
 *   1. Menghapus seluruh seeder kategori & kelas/jurusan lama agar dapat
 *      diinput langsung oleh Super Admin / Admin melalui Dashboard.
 *   2. Menyisakan akun-akun utama (1 Super Admin, 2 Guru Admin: Ibu Silvi Dwi
 *      Manitik & Ibu Melinda, 1 Pembeli, serta akun Siswa Pemilik Toko dengan
 *      prinsip Satu Orang Satu Toko).
 *   3. Membuat 1 Contoh Produk Siswa saja:
 *      - `Gantungan Kunci Bunga` (Rp 2.000) milik Khoiriyah Muntazah (XI TKJT).
 *   4. Menyiapkan 3 Contoh Iklan Mitra Kerjasama Sekolah pada CMS (`section = 'iklan'`):
 *      - Bank Syariah Indonesia (BSI) — durasi tampil 4 detik
 *      - PT. Mega Mentari — durasi tampil 5 detik
 *      - Daihatsu — durasi tampil 6 detik
 *      beserta rentang masa kontrak (`contract_start` s/d `contract_end`) yang
 *      dapat diatur oleh Super Admin pada CMS.
 *
 * Algoritma:
 *   1. Menyimpan snapshot perubahan user & CMS ke `storage/app/live_seed_snapshot.json`.
 *   2. Membersihkan seeder produk/toko/kategori lama agar hanya tersisa 1 contoh
 *      produk (`gantungan-kunci-bunga`).
 *   3. Melakukan seeding akun, 1 contoh produk, dan 3 iklan mitra kerjasama.
 * ============================================================================
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Menyimpan snapshot data `users` dan `cms_contents` terkini dari database
     * ke `storage/app/live_seed_snapshot.json` agar perubahan yang dilakukan
     * pengguna di CMS atau manajemen akun selalu tersimpan.
     */
    public static function syncLiveSnapshotToStorage(): void
    {
        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            $snapshotPath = storage_path('app/live_seed_snapshot.json');
            $existingSnapshot = is_file($snapshotPath)
                ? (json_decode((string) file_get_contents($snapshotPath), true) ?: [])
                : [];

            $users = User::whereNotIn('email', [
                'admin@hilalkraft.sch.id',
                'fauzan.siswa@hilalkraft.sch.id',
                'hendra.guru@hilalkraft.sch.id',
                'nisa.pembeli@gmail.com',
                'rizky.siswa@hilalkraft.sch.id',
            ])->get()->keyBy('email')->map(function (User $u) {
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
                $cms = CmsContent::orderBy('sort_order')->get()->keyBy('key')->map(function (CmsContent $c) {
                    return [
                        'key'              => $c->key,
                        'section'          => $c->section,
                        'title'            => $c->title,
                        'partner_name'     => $c->partner_name,
                        'badge_text'       => $c->badge_text,
                        'content'          => $c->content,
                        'image'            => $c->image,
                        'link_url'         => $c->link_url,
                        'duration_seconds' => (int) ($c->duration_seconds ?: 5),
                        'contract_start'   => $c->contract_start ? $c->contract_start->format('Y-m-d') : null,
                        'contract_end'     => $c->contract_end ? $c->contract_end->format('Y-m-d') : null,
                        'theme_color'      => $c->theme_color ?: 'emerald',
                        'is_active'        => (bool) $c->is_active,
                        'sort_order'       => (int) $c->sort_order,
                    ];
                })->toArray();
            }

            if (! empty($users) || ! empty($cms)) {
                $payload = [
                    'updated_at' => date('c'),
                    'users'      => ! empty($users) ? $users : ($existingSnapshot['users'] ?? []),
                    'cms'        => ! empty($cms) ? $cms : ($existingSnapshot['cms'] ?? []),
                ];
                @file_put_contents($snapshotPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } catch (Throwable $e) {
            // Abaikan bila gagal menulis snapshot
        }
    }

    public function run(): void
    {
        self::syncLiveSnapshotToStorage();

        $snapshotPath = storage_path('app/live_seed_snapshot.json');
        $snapshot = is_file($snapshotPath)
            ? (json_decode((string) file_get_contents($snapshotPath), true) ?: [])
            : [];
        $snapshotUsers = $snapshot['users'] ?? [];
        $snapshotCms   = $snapshot['cms'] ?? [];

        // 1. Bersihkan semua seeder produk, toko, akun dummy, dan kategori lama
        Product::whereIn('slug', [
            'lampu-meja-kayu-sora',
            'kaligrafi-kayu-ayat-kursi-ukir',
            'lampu-meja-bambu-anyam-minimalis',
            'brownies-lumer-kurma-al-hilal',
            'gantungan-kunci-karakter-iyung-iyung',
            'gantungan-kunci-karakter-5k-yulianti',
        ])->delete();

        Store::whereIn('slug', [
            'studio-karya-rizky',
            'galeri-kriya-fauzan',
            'karya-inovasi-pak-hendra',
            'tokoganci-iyung-yulianti',
        ])->delete();

        User::whereIn('email', [
            'admin@hilalkraft.sch.id',
            'fauzan.siswa@hilalkraft.sch.id',
            'hendra.guru@hilalkraft.sch.id',
            'nisa.pembeli@gmail.com',
            'rizky.siswa@hilalkraft.sch.id',
        ])->delete();

        // Hapus seeder kategori bawaan agar diinput langsung oleh Admin/Super Admin
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

        // Hapus seeder kelas & jurusan bawaan lama
        ClassMajor::query()->delete();

        // 2. Akun Inti (Super Admin, 2 Guru Admin: Ibu Silvi & Ibu Melinda, 1 Pembeli)
        $coreUsers = [
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
            'pembeli@hilalkraft.sch.id' => [
                'name'           => 'Aditya Pratama',
                'password_plain' => 'password123',
                'role'           => 'buyer',
                'user_subtype'   => 'pelajar',
                'nis_nisn'       => '262731002',
                'school_origin'  => 'SMK Plus Al Hilal',
                'class_name'     => 'X',
                'major_name'     => 'Teknik Otomotif (TO)',
                'phone'          => '081298765432',
                'desa'           => 'Tegalgubug',
                'kecamatan'      => 'Arjawinangun',
                'kabupaten'      => 'Cirebon',
                'kode_pos'       => '45162',
                'address_detail' => 'Blok 04 Kebon Kelapa Lor, Desa Tegalgubug, Kec. Arjawinangun, Kab. Cirebon',
                'is_active'      => true,
            ],
        ];

        foreach ($coreUsers as $email => $defaultAttrs) {
            $attrs = array_merge($defaultAttrs, $snapshotUsers[$email] ?? []);
            $plainPass = $attrs['password_plain'] ?? 'password123';
            $attrs['password'] = Hash::make($plainPass);
            unset($attrs['email']);

            User::firstOrCreate(
                ['email' => $email],
                $attrs
            );
        }

        // 3. Akun Siswa (Satu Orang Satu Toko) & 1 Contoh Produk Saja
        // 3a. Khoiriyah Muntazah (Kelas XI TKJT) — Memiliki 1 Contoh Produk: Gantungan Kunci Bunga
        $khoiriyahEmail = 'khoiriyah.siswa@hilalkraft.sch.id';
        $khoiriyahDefaults = [
            'name'           => 'Khoiriyah Muntazah',
            'password_plain' => 'password123',
            'role'           => 'seller',
            'user_subtype'   => 'siswa',
            'is_teacher'     => false,
            'nis_nisn'       => '252611014',
            'class_name'     => 'XI',
            'major_name'     => 'Teknik Komputer dan Jaringan Telekomunikasi (TKJT)',
            'phone'          => '082129380141',
            'desa'           => 'Tegalgubug',
            'kecamatan'      => 'Arjawinangun',
            'kabupaten'      => 'Cirebon',
            'kode_pos'       => '45162',
            'address_detail' => 'Blok 05 Rembes, Desa Tegalgubug, Kec. Arjawinangun, Kab. Cirebon 45162',
            'is_active'      => true,
        ];
        $khoiriyahAttrs = array_merge($khoiriyahDefaults, $snapshotUsers[$khoiriyahEmail] ?? []);
        $khoiriyahAttrs['password'] = Hash::make($khoiriyahAttrs['password_plain'] ?? 'password123');
        unset($khoiriyahAttrs['email']);

        $khoiriyah = User::firstOrCreate(
            ['email' => $khoiriyahEmail],
            $khoiriyahAttrs
        );

        $storeKhoiriyah = Store::updateOrCreate(
            ['user_id' => $khoiriyah->id],
            [
                'store_name'           => 'Galeri Ganci Khoiriyah Muntazah',
                'slug'                 => 'toko-khoiriyah-muntazah',
                'seller_type'          => 'siswa',
                'student_class'        => 'XI TKJT',
                'description'          => 'Etalase resmi karya kerajinan tangan Gantungan Kunci Bunga milik Khoiriyah Muntazah (Kelas XI TKJT SMK Plus Al Hilal).',
                'contact_number'       => '6282129380141',
                'is_verified'          => true,
                'views_count'          => 34,
                'discount_pelajar'     => 10,
                'discount_keluarga'    => 15,
                'discount_alumni'      => 10,
                'discount_bazar_extra' => 5,
            ]
        );

        // Satu Contoh Produk Saja: Gantungan Kunci Bunga (Khoiriyah Muntazah)
        Product::updateOrCreate(
            ['slug' => 'gantungan-kunci-bunga'],
            [
                'store_id'     => $storeKhoiriyah->id,
                'product_name' => 'Gantungan Kunci Bunga',
                'category'     => 'Kerajinan',
                'description'  => 'Gantungan Kunci Bunga — Manis, Simple, Bikin Semangat! Kecil tapi penuh makna. Keunggulan produk: Desain unik & lucu, bahan kawat bulu berkualitas & awet, cocok untuk hadiah atau koleksi pribadi, ringan mudah dibawa kemana-mana, ukuran pas di tangan, dan bisa dipasang di tas, kunci, dll. Teman kecil yang selalu bikin hari lebih berwarna. Karya Khoiriyah Muntazah (Kelas XI TKJT SMK Plus Al Hilal). Hubungi WA: +62 821-2938-0141.',
                'price'        => 2000,
                'stock'        => 35,
                'views_count'  => 42,
                'likes_count'  => 14,
                'image'        => 'products/gantungan-kunci-bunga.jpg',
                'is_active'    => true,
            ]
        );

        // 3b. Akun Yuliyanti (Kelas XI TKJT) — Satu Orang Satu Toko
        $yuliantiEmail = 'yulianti.siswa@hilalkraft.sch.id';
        $yuliantiDefaults = [
            'name'           => 'Yuliyanti',
            'password_plain' => 'password123',
            'role'           => 'seller',
            'user_subtype'   => 'siswa',
            'is_teacher'     => false,
            'nis_nisn'       => '252611036',
            'class_name'     => 'XI',
            'major_name'     => 'Teknik Komputer dan Jaringan Telekomunikasi (TKJT)',
            'phone'          => '082129380141',
            'desa'           => 'Tegalgubug Lor',
            'kecamatan'      => 'Arjawinangun',
            'kabupaten'      => 'Cirebon',
            'kode_pos'       => '45162',
            'address_detail' => 'Blok 03 Walisanga, Desa Tegalgubug Lor, Kec. Arjawinangun, Kab. Cirebon 45162',
            'is_active'      => true,
        ];
        $yuliantiAttrs = array_merge($yuliantiDefaults, $snapshotUsers[$yuliantiEmail] ?? []);
        $yuliantiAttrs['name'] = 'Yuliyanti';
        $yuliantiAttrs['password'] = Hash::make($yuliantiAttrs['password_plain'] ?? 'password123');
        unset($yuliantiAttrs['email']);

        $yulianti = User::firstOrCreate(
            ['email' => $yuliantiEmail],
            $yuliantiAttrs
        );
        if ($yulianti->name !== 'Yuliyanti') {
            $yulianti->update(['name' => 'Yuliyanti']);
        }

        Store::updateOrCreate(
            ['user_id' => $yulianti->id],
            [
                'store_name'           => 'TokoGanci Iyung Iyung',
                'slug'                 => 'tokoganci-iyung-iyung',
                'seller_type'          => 'siswa',
                'student_class'        => 'XI TKJT',
                'description'          => 'Etalase resmi kerajinan gantungan kunci karakter milik Yuliyanti (Kelas XI TKJT SMK Plus Al Hilal).',
                'contact_number'       => '6282129380141',
                'is_verified'          => true,
                'views_count'          => 26,
                'discount_pelajar'     => 10,
                'discount_keluarga'    => 15,
                'discount_alumni'      => 10,
                'discount_bazar_extra' => 5,
            ]
        );

        if (Schema::hasTable('bazaar_events')) {
            \App\Models\BazaarEvent::firstOrCreate(
                ['voucher_code' => 'BAZARSMKPAL'],
                [
                    'title'               => 'Bazar Karya & Inovasi SMK Plus Al Hilal (SMK PAL)',
                    'location'            => 'Halaman Utama SMK Plus Al Hilal Arjawinangun',
                    'description'         => 'Diskon spesial bagi pembeli yang hadir langsung atau berbelanja pada acara Bazar Karya & Inovasi SMK Plus Al Hilal.',
                    'discount_percent'    => 10,
                    'max_discount_amount' => null,
                    'start_date'          => date('Y-m-d'),
                    'end_date'            => date('Y-m-d', strtotime('+6 months')),
                    'is_active'           => true,
                ]
            );
        }

        // Contoh Data Transaksi untuk Laporan (Penjual, Pembeli, Admin/Super Admin)
        if (Schema::hasTable('orders') && Schema::hasTable('order_items')) {
            $productBunga = Product::where('slug', 'gantungan-kunci-bunga')->first();
            $buyerAditya = User::where('email', 'pembeli@hilalkraft.sch.id')->first();
            $bazaarEvent = \App\Models\BazaarEvent::where('voucher_code', 'BAZARSMKPAL')->first();

            if ($productBunga && $buyerAditya && $storeKhoiriyah) {
                $sampleOrders = [
                    [
                        'invoice'     => 'HK-20261104-001',
                        'date'        => now()->subHours(2),
                        'qty'         => 3,
                        'price'       => 2000,
                        'disc_pct'    => 10,
                        'disc_amt'    => 600,
                        'total'       => 5400,
                        'status'      => 'confirmed',
                        'progress'    => 'kemas',
                        'note'        => 'Sedang dikemas dengan pita merah',
                        'rating'      => null,
                        'review'      => null,
                    ],
                    [
                        'invoice'     => 'HK-20261103-RPFAA',
                        'date'        => now()->subDays(1)->setTime(23, 57),
                        'qty'         => 5,
                        'price'       => 2000,
                        'disc_pct'    => 10,
                        'disc_amt'    => 1000,
                        'total'       => 9000,
                        'status'      => 'completed',
                        'progress'    => 'sampai',
                        'note'        => 'Barang telah diterima pembeli di stand sekolah',
                        'rating'      => 5,
                        'review'      => 'Karya ganci bunganya cantik dan rapi, bahan kawat bulunya lembut dan kuat!',
                    ],
                    [
                        'invoice'     => 'HK-20261028-003',
                        'date'        => now()->subDays(7)->setTime(14, 20),
                        'qty'         => 8,
                        'price'       => 2000,
                        'disc_pct'    => 10,
                        'disc_amt'    => 1600,
                        'total'       => 14400,
                        'status'      => 'completed',
                        'progress'    => 'sampai',
                        'note'        => 'Pesanan COD area Al Hilal',
                        'rating'      => 5,
                        'review'      => 'Pelayanan cepat dan ramah via WA. Sukses terus untuk karya siswa SMK PAL!',
                    ],
                    [
                        'invoice'     => 'HK-20261015-004',
                        'date'        => now()->subDays(20)->setTime(10, 15),
                        'qty'         => 12,
                        'price'       => 2000,
                        'disc_pct'    => 15,
                        'disc_amt'    => 3600,
                        'total'       => 20400,
                        'status'      => 'completed',
                        'progress'    => 'sampai',
                        'note'        => 'Pesanan suvenir bazar kelas',
                        'rating'      => 5,
                        'review'      => 'Bagus sekali untuk suvenir!',
                    ],
                ];

                foreach ($sampleOrders as $sOrd) {
                    $order = \App\Models\Order::firstOrCreate(
                        ['invoice_number' => $sOrd['invoice']],
                        [
                            'buyer_id'                    => $buyerAditya->id,
                            'store_id'                    => $storeKhoiriyah->id,
                            'subtotal_price'              => $sOrd['qty'] * $sOrd['price'],
                            'buyer_type_discount_percent' => $sOrd['disc_pct'],
                            'buyer_type_discount_amount'  => $sOrd['disc_amt'],
                            'bazaar_event_id'             => $bazaarEvent?->id,
                            'bazaar_discount_percent'     => 0,
                            'bazaar_discount_amount'      => 0,
                            'total_price'                 => $sOrd['total'],
                            'status'                      => $sOrd['status'],
                            'progress_status'             => $sOrd['progress'],
                            'progress_note'               => $sOrd['note'],
                            'shipping_address'            => 'Blok Rembes belakang musholah, Arjawinangun',
                            'buyer_rating'                => $sOrd['rating'],
                            'buyer_review'                => $sOrd['review'],
                            'rated_at'                    => $sOrd['rating'] ? $sOrd['date'] : null,
                            'created_at'                  => $sOrd['date'],
                            'updated_at'                  => $sOrd['date'],
                        ]
                    );

                    \App\Models\OrderItem::firstOrCreate(
                        ['order_id' => $order->id, 'product_id' => $productBunga->id],
                        [
                            'quantity'   => $sOrd['qty'],
                            'unit_price' => $sOrd['price'],
                            'subtotal'   => $sOrd['qty'] * $sOrd['price'],
                            'created_at' => $sOrd['date'],
                            'updated_at' => $sOrd['date'],
                        ]
                    );
                }
            }
        }

        // 4. Contoh Iklan Mitra Kerjasama Sekolah (BSI, PT. Mega Mentari, Daihatsu) & Konten CMS
        $today = date('Y-m-d');
        $sixMonthsLater = date('Y-m-d', strtotime('+6 months'));
        $oneYearLater = date('Y-m-d', strtotime('+1 year'));

        $partnerAdsDefaults = [
            [
                'key'              => 'iklan_bsi',
                'section'          => 'iklan',
                'title'            => 'Layanan Perbankan Syariah & Literasi Keuangan Siswa bersama BSI',
                'partner_name'     => 'Bank Syariah Indonesia (BSI)',
                'badge_text'       => 'MITRA PERBANKAN SYARIAH',
                'content'          => 'Kerjasama resmi SMK Plus Al Hilal bersama Bank Syariah Indonesia (BSI) dalam layanan transaksi halal, tabungan pelajar, dan inkubasi wirausaha muda.',
                'link_url'         => '#katalog-produk',
                'duration_seconds' => 4,
                'contract_start'   => $today,
                'contract_end'     => $sixMonthsLater,
                'theme_color'      => 'bsi',
                'is_active'        => true,
                'sort_order'       => 1,
            ],
            [
                'key'              => 'iklan_mega_mentari',
                'section'          => 'iklan',
                'title'            => 'Kemitraan Industri & Penyaluran Lulusan bersama PT. Mega Mentari',
                'partner_name'     => 'PT. Mega Mentari',
                'badge_text'       => 'MITRA INDUSTRI RESMI',
                'content'          => 'Sinergi praktek kerja industri, peningkatan kompetensi siswa, dan rekrutmen lulusan siap kerja SMK Plus Al Hilal bersama PT. Mega Mentari.',
                'link_url'         => '#katalog-produk',
                'duration_seconds' => 5,
                'contract_start'   => $today,
                'contract_end'     => $oneYearLater,
                'theme_color'      => 'megamentari',
                'is_active'        => true,
                'sort_order'       => 2,
            ],
            [
                'key'              => 'iklan_daihatsu',
                'section'          => 'iklan',
                'title'            => 'Pengembangan Kompetensi Vokasi Otomotif bersama Daihatsu',
                'partner_name'     => 'Daihatsu',
                'badge_text'       => 'MITRA OTOMOTIF & KURIKULUM',
                'content'          => 'Dukungan kurikulum standar industri, budaya kerja teknisi, dan inovasi teknologi otomotif SMK Plus Al Hilal bersama Daihatsu.',
                'link_url'         => '#katalog-produk',
                'duration_seconds' => 6,
                'contract_start'   => $today,
                'contract_end'     => $oneYearLater,
                'theme_color'      => 'daihatsu',
                'is_active'        => true,
                'sort_order'       => 3,
            ],
        ];

        foreach ($partnerAdsDefaults as $adItem) {
            $mergedAd = array_merge($adItem, $snapshotCms[$adItem['key']] ?? []);
            CmsContent::firstOrCreate(
                ['key' => $adItem['key']],
                $mergedAd
            );
        }

        // 5. Konten Default CMS Website (Menggunakan firstOrCreate agar editan CMS pengguna tidak tertimpa)
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

        // Hapus item CMS lama yang sudah tidak dipakai agar tabel CMS bersih
        CmsContent::whereIn('key', ['story_badge', 'story_title', 'story_description', 'hero_stats_text'])->delete();

        CmsContent::clearCache();
        self::syncLiveSnapshotToStorage();
    }
}
