<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PDO;
use Throwable;

/**
 * ============================================================================
 * SERVICE PROVIDER: APP SERVICE PROVIDER (AUTO-BOOTSTRAPPER DB, MIGRATION & CMS)
 * ============================================================================
 * Fungsi:
 *   Menyiapkan koneksi database (MySQL `db_hilalkraft` dengan fallback SQLite),
 *   menjalankan migrasi skema terbaru (termasuk `cms_contents`), dan memastikan
 *   data awal yang diminta pengguna tersedia secara otomatis satu kali:
 *   - 1 Super Admin (`superadmin@hilalkraft.sch.id`)
 *   - 2 Guru sebagai Admin: Ibu Silvi Dwi Manitik & Ibu Melinda
 *   - 1 Siswa Pemilik Toko + 1 Karya Siswa Contoh (`Gantungan Kunci Bunga`)
 *   - 1 Pembeli (`Aditya Pratama`)
 *   - Blok Konten CMS Website (`cms_contents`)
 * Algoritma:
 *   1. Mencoba koneksi ke MySQL (`db_hilalkraft`) dan beralih ke SQLite lokal
 *      apabila layanan MySQL sedang tidak aktif.
 *   2. Menjalankan `migrate --force` secara otomatis apabila tabel baru seperti
 *      `cms_contents` belum tersedia.
 *   3. Menggunakan penanda satu kali (`cms_seeded_v2_{connection}.flag`) agar
 *      sinkronisasi data contoh berjalan satu kali pada masing-masing database
 *      tanpa menimpa perubahan yang dilakukan pengguna selanjutnya.
 * ============================================================================
 */
class AppServiceProvider extends ServiceProvider
{
    private static bool $initialized = false;

    public function register(): void
    {
        Config::set('session.driver', 'file');
        Config::set('cache.default', 'file');
    }

    public function boot(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        try {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database', 'db_hilalkraft');
            $username = config('database.connections.mysql.username', 'root');
            $password = config('database.connections.mysql.password', '');

            $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            DB::purge('mysql');

            $this->ensureSchemaAndSampleData();
        } catch (Throwable $e) {
            try {
                $sqlitePath = database_path('database.sqlite');
                Config::set('database.default', 'sqlite');
                Config::set('database.connections.sqlite.database', $sqlitePath);
                DB::purge('sqlite');
                DB::setDefaultConnection('sqlite');

                $this->ensureSchemaAndSampleData();
            } catch (Throwable $inner) {
                // Abaikan jika gagal
            }
        }
    }

    private function ensureSchemaAndSampleData(): void
    {
        $conn = DB::getDefaultConnection();

        // 1. Self-Healing Kolom Tabel Stores & Users (Mencegah error kolom truncated / missing)
        try {
            if ($conn === 'mysql') {
                DB::statement("ALTER TABLE `stores` MODIFY COLUMN `student_class` VARCHAR(255) NULL;");
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `class_name` VARCHAR(100) NULL;");
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `major_name` VARCHAR(255) NULL;");
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel belum terbuat
        }

        try {
            if (Schema::hasTable('stores')) {
                Schema::table('stores', function ($table) {
                    if (! Schema::hasColumn('stores', 'deleted_at')) {
                        $table->softDeletes();
                    }
                    if (! Schema::hasColumn('stores', 'status')) {
                        $table->string('status', 20)->default('active');
                    }
                });
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel dalam status locking
        }

        try {
            if (Schema::hasTable('orders')) {
                Schema::table('orders', function ($table) {
                    if (! Schema::hasColumn('orders', 'delivery_method')) {
                        $table->string('delivery_method', 30)->default('pickup');
                    }
                    if (! Schema::hasColumn('orders', 'shipping_cost')) {
                        $table->decimal('shipping_cost', 12, 2)->default(0);
                    }
                    if (! Schema::hasColumn('orders', 'destination_kabupaten')) {
                        $table->string('destination_kabupaten', 100)->nullable();
                    }
                    if (! Schema::hasColumn('orders', 'destination_kecamatan')) {
                        $table->string('destination_kecamatan', 100)->nullable();
                    }
                    if (! Schema::hasColumn('orders', 'destination_desa')) {
                        $table->string('destination_desa', 100)->nullable();
                    }
                    if (! Schema::hasColumn('orders', 'courier_name')) {
                        $table->string('courier_name', 100)->nullable();
                    }
                    if (! Schema::hasColumn('orders', 'tracking_number')) {
                        $table->string('tracking_number', 100)->nullable();
                    }
                });
            }
        } catch (Throwable $e) {
            // Abaikan jika orders belum siap
        }

        if (
            ! Schema::hasTable('users') ||
            ! Schema::hasTable('categories') ||
            ! Schema::hasTable('class_majors') ||
            ! Schema::hasTable('product_comments') ||
            ! Schema::hasTable('product_likes') ||
            ! Schema::hasTable('visitor_logs') ||
            ! Schema::hasTable('cms_contents') ||
            ! Schema::hasTable('bazaar_events') ||
            ! Schema::hasTable('activity_logs') ||
            ! Schema::hasColumn('cms_contents', 'duration_seconds') ||
            ! Schema::hasColumn('orders', 'progress_status') ||
            ! Schema::hasColumn('orders', 'delivery_method') ||
            ! Schema::hasColumn('stores', 'discount_pelajar') ||
            ! Schema::hasColumn('users', 'user_subtype') ||
            ! Schema::hasColumn('products', 'views_count')
        ) {
            Artisan::call('migrate', ['--force' => true]);
        }

        // Pastikan minimal 1 contoh Acara Bazar SMK aktif tersedia jika tabel bazaar_events masih kosong
        if (Schema::hasTable('bazaar_events') && \App\Models\BazaarEvent::count() === 0) {
            \App\Models\BazaarEvent::create([
                'title'               => 'Bazar Karya & Inovasi SMK Plus Al Hilal (SMK PAL)',
                'voucher_code'        => 'BAZARSMKPAL',
                'location'            => 'Halaman Utama SMK Plus Al Hilal Arjawinangun',
                'description'         => 'Diskon spesial bagi pembeli yang hadir langsung atau berbelanja pada acara Bazar Karya & Inovasi SMK Plus Al Hilal.',
                'discount_percent'    => 10,
                'max_discount_amount' => null,
                'start_date'          => date('Y-m-d'),
                'end_date'            => date('Y-m-d', strtotime('+6 months')),
                'is_active'           => true,
            ]);

            if (Schema::hasColumn('stores', 'discount_pelajar')) {
                \App\Models\Store::where('discount_pelajar', 0)
                    ->where('discount_keluarga', 0)
                    ->update([
                        'discount_pelajar'     => 10,
                        'discount_keluarga'    => 15,
                        'discount_alumni'      => 10,
                        'discount_bazar_extra' => 5,
                    ]);
            }
        }

        // Sinkronisasi satu kali (One-Time Flag v7):
        // - Menghapus seeder kategori & produk lama, menyisakan akun utama + 1 contoh produk
        // - Menambahkan 3 contoh Iklan Mitra Kerjasama Sekolah (BSI, PT. Mega Mentari, Daihatsu)
        $connName = DB::getDefaultConnection();
        $seedFlag = storage_path("app/cms_seeded_v7_{$connName}.flag");
        if (! is_file($seedFlag)) {
            app(DatabaseSeeder::class)->run();

            @file_put_contents($seedFlag, date('c'));
        } elseif ($connName === 'mysql') {
            DatabaseSeeder::syncLiveSnapshotToStorage();
        }
    }
}
