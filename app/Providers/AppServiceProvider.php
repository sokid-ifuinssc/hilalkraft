<?php

namespace App\Providers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use PDO;
use Throwable;

/**
 * ============================================================================
 * SERVICE PROVIDER: APP SERVICE PROVIDER (AUTO-BOOTSTRAPPER DB & MIGRATION)
 * ============================================================================
 * Menyiapkan koneksi database (MySQL dengan fallback SQLite), memastikan
 * migrasi skema terbaru berjalan jika tabel inti belum ada, melengkapi token
 * QR bazar yang kosong, dan menjalankan seeder non-destruktif hanya saat
 * database benar-benar kosong (belum memiliki user).
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

        $this->registerImpersonationViewComposer();
    }

    /**
     * Membagikan daftar seluruh user untuk fitur "Lihat Sebagai" kepada Super Admin dan Admin
     * (termasuk saat sesi simulasi sedang aktif) tanpa membebani pengunjung publik.
     */
    private function registerImpersonationViewComposer(): void
    {
        View::composer(['layouts.app', 'layouts.panel', 'admin.dashboard'], function ($view) {
            static $cache = [];

            $cacheKey = (auth()->id() ?? 'guest') . '|' . (session('impersonator_id') ?? 'none');
            if (! array_key_exists($cacheKey, $cache)) {
                $actor = null;
                $users = collect();
                try {
                    if (auth()->check()) {
                        $currentUser = auth()->user();
                        $impId = session('impersonator_id');

                        if ($impId) {
                            $candidate = User::find($impId);
                            if ($candidate && $candidate->is_active && in_array($candidate->role, ['super_admin', 'admin'], true)) {
                                $actor = $candidate;
                            }
                        } elseif ($currentUser && $currentUser->is_active && in_array($currentUser->role, ['super_admin', 'admin'], true)) {
                            $actor = $currentUser;
                        }

                        if ($actor && Schema::hasTable('users')) {
                            $users = User::with('store:id,user_id,store_name')
                                ->select('id', 'name', 'email', 'role', 'user_subtype', 'is_teacher', 'class_name', 'major_name', 'is_active')
                                ->where('is_active', true)
                                ->when($actor->role !== 'super_admin', fn ($q) => $q->where('role', '!=', 'super_admin'))
                                ->orderByRaw("CASE role WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 WHEN 'seller' THEN 3 ELSE 4 END")
                                ->orderBy('name')
                                ->get();
                        }
                    }
                } catch (Throwable $e) {
                    $actor = null;
                    $users = collect();
                }

                $cache[$cacheKey] = ['actor' => $actor, 'users' => $users];
            }

            $view->with('impersonationActor', $cache[$cacheKey]['actor']);
            $view->with('impersonatableUsers', $cache[$cacheKey]['users']);
        });
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
                    if (! Schema::hasColumn('stores', 'bank_name')) {
                        $table->string('bank_name', 50)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'bank_account_number')) {
                        $table->string('bank_account_number', 50)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'bank_account_name')) {
                        $table->string('bank_account_name', 100)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'ewallet_name')) {
                        $table->string('ewallet_name', 50)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'ewallet_account_number')) {
                        $table->string('ewallet_account_number', 50)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'ewallet_account_name')) {
                        $table->string('ewallet_account_name', 100)->nullable();
                    }
                    if (! Schema::hasColumn('stores', 'payment_instructions')) {
                        $table->text('payment_instructions')->nullable();
                    }
                });
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel dalam status locking
        }

        try {
            if (Schema::hasTable('products')) {
                Schema::table('products', function ($table) {
                    if (! Schema::hasColumn('products', 'allowed_delivery_methods')) {
                        $table->json('allowed_delivery_methods')->nullable();
                    }
                    if (! Schema::hasColumn('products', 'gallery_images')) {
                        $table->json('gallery_images')->nullable();
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

        try {
            if (
                ! Schema::hasTable('users') ||
                ! Schema::hasTable('regencies') ||
                ! Schema::hasTable('bazaar_doorprize_winners') ||
                ! Schema::hasColumn('bazaar_events', 'points_checkin')
            ) {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (Throwable $e) {
            // Abaikan jika migrasi sedang berjalan
        }

        // Lengkapi token QR & kode pendek pada acara bazar yang belum memilikinya
        try {
            if (Schema::hasTable('bazaar_events') && Schema::hasColumn('bazaar_events', 'checkin_token')) {
                \App\Models\BazaarEvent::whereNull('checkin_token')
                    ->orWhereNull('short_code')
                    ->get()
                    ->each(function (\App\Models\BazaarEvent $ev) {
                        $ev->ensureTokensExist();
                    });
            }
        } catch (Throwable $e) {
            // Abaikan jika belum siap
        }

        // Jalankan seeder non-destruktif hanya pada instalasi database kosong (belum ada akun sama sekali)
        if (Schema::hasTable('users') && \App\Models\User::count() === 0) {
            app(DatabaseSeeder::class)->run();
        }
    }
}
