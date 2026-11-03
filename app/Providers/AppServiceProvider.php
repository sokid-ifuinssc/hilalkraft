<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use PDO;
use Throwable;

/**
 * ============================================================================
 * SERVICE PROVIDER: APP SERVICE PROVIDER (AUTO-BOOTSTRAPPER DB & MIGRATION)
 * ============================================================================
 * Fungsi:
 *   Menyiapkan layanan aplikasi saat booting, termasuk memastikan database
 *   `db_hilalkraft`, tabel migrasi (`users`, `stores`, `products`, `orders`,
 *   `categories`, `class_majors`), serta data awal otomatis tersedia.
 *
 * Algoritma (Self-Healing Schema & Incremental Master Data Seeding):
 *   1. Mengatur driver session dan cache ke `file`.
 *   2. Membuka koneksi PDO ke MySQL lokal (`127.0.0.1:3306`) dan mengeksekusi
 *      `CREATE DATABASE IF NOT EXISTS db_hilalkraft`.
 *   3. Mengecek tabel baru (`categories`, `class_majors`) dan kolom (`password_plain`,
 *      `seller_type`). Jika belum ada, jalankan `Artisan::call('migrate', ['--force' => true])`.
 *   4. Mengisi data awal Kategori Produk dan Kelas/Jurusan apabila tabel masih kosong.
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

            $this->ensureSchemaAndMasterData();
        } catch (Throwable $e) {
            try {
                $sqlitePath = database_path('database.sqlite');
                Config::set('database.default', 'sqlite');
                Config::set('database.connections.sqlite.database', $sqlitePath);
                DB::purge('sqlite');
                DB::setDefaultConnection('sqlite');

                $this->ensureSchemaAndMasterData();
            } catch (Throwable $inner) {
                // Abaikan jika gagal
            }
        }
    }

    /**
     * Fungsi: Memastikan seluruh tabel, kolom, dan data master awal tersedia.
     * Algoritma: Schema Introspection + Incremental Migration + Idempotent Seeding.
     */
    private function ensureSchemaAndMasterData(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('categories') || ! Schema::hasTable('class_majors')) {
            Artisan::call('migrate', ['--force' => true]);
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'password_plain')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_plain')->nullable()->after('password');
            });
        }

        if (Schema::hasTable('stores') && ! Schema::hasColumn('stores', 'seller_type')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('seller_type', 30)->default('siswa')->after('slug');
            });
        }

        if (Schema::hasTable('users') && User::count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password_plain')) {
            User::whereNull('password_plain')->update(['password_plain' => 'password123']);
        }

        // Seed Kategori Produk Awal jika kosong
        if (Schema::hasTable('categories') && Category::count() === 0) {
            $defaultCategories = [
                'Kerajinan Tangan',
                'Desain Grafis & Cetak',
                'Kuliner & Olahan Pangan',
                'Busana & Tekstil',
                'Teknologi & IoT Siswa',
                'Seni Kaligrafi & Islami',
            ];
            foreach ($defaultCategories as $catName) {
                Category::create([
                    'name'        => $catName,
                    'slug'        => Str::slug($catName),
                    'description' => 'Kategori produk karya ' . $catName,
                ]);
            }
        }

        // Seed Kelas, Jurusan, & Unit Guru Awal jika kosong
        if (Schema::hasTable('class_majors') && ClassMajor::count() === 0) {
            $defaultUnits = [
                ['class_name' => 'X', 'major_name' => 'Desain Komunikasi Visual (DKV)'],
                ['class_name' => 'XI', 'major_name' => 'Desain Komunikasi Visual (DKV)'],
                ['class_name' => 'XII', 'major_name' => 'Desain Komunikasi Visual (DKV)'],
                ['class_name' => 'X', 'major_name' => 'Kriya Kreatif & Kerajinan'],
                ['class_name' => 'XI', 'major_name' => 'Kriya Kreatif & Kerajinan'],
                ['class_name' => 'XII', 'major_name' => 'Kriya Kreatif & Kerajinan'],
                ['class_name' => 'XI', 'major_name' => 'Tata Busana & Tekstil'],
                ['class_name' => 'XII', 'major_name' => 'Kuliner & Tata Boga'],
                ['class_name' => 'Guru / Pembina', 'major_name' => 'Unit Kewirausahaan Guru SMK Plus Al Hilal'],
                ['class_name' => 'Jurusan', 'major_name' => 'Unit Produksi DKV & Percetakan'],
                ['class_name' => 'Jurusan', 'major_name' => 'Unit Produksi Kriya & Kaligrafi'],
                ['class_name' => 'Jurusan', 'major_name' => 'Unit Produksi Kuliner & Boga'],
            ];
            foreach ($defaultUnits as $unit) {
                ClassMajor::create($unit);
            }
        }
    }
}
