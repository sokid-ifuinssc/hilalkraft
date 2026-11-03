<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL CATEGORIES & CLASS_MAJORS (KATEGORI PRODUK & KELAS/JURUSAN)
 * ============================================================================
 * Fungsi:
 *   1. Tabel `categories`: Menyimpan daftar kategori produk dinamis yang dapat
 *      ditambah, diubah, dan dihapus oleh Super Admin maupun Admin.
 *   2. Tabel `class_majors`: Menyimpan master data Kelas, Jurusan, dan Unit Guru
 *      di SMK Plus Al Hilal yang digunakan saat pembuatan akun Toko (Siswa, Guru,
 *      maupun Jurusan).
 *   3. Kolom `seller_type` pada `stores`: Membedakan jenis pemilik toko
 *      (`siswa`, `guru`, `jurusan`).
 *
 * Algoritma (Normalized Master Data Schema):
 *   Menggunakan indeks unik pada `slug` kategori dan atribut `seller_type`
 *   pada tabel `stores` untuk mendukung pemilik toko dari kalangan Siswa,
 *   Guru, maupun Unit Produksi Jurusan.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('class_majors', function (Blueprint $table) {
            $table->id();
            $table->string('class_name', 50)->comment('Contoh: X, XI, XII, Guru, Unit Jurusan');
            $table->string('major_name', 120)->comment('Contoh: Desain Komunikasi Visual (DKV)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('stores') && ! Schema::hasColumn('stores', 'seller_type')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->enum('seller_type', ['siswa', 'guru', 'jurusan'])->default('siswa')->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'seller_type')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('seller_type');
            });
        }
        Schema::dropIfExists('class_majors');
        Schema::dropIfExists('categories');
    }
};

