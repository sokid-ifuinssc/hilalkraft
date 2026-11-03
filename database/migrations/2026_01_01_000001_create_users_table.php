<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL USERS, PASSWORD_RESET_TOKENS, DAN SESSIONS
 * ============================================================================
 * Fungsi:
 *   Membangun skema tabel utama `users` di database `db_hilalkraft` untuk
 *   menyimpan kredensial dan profil 4 kategori pengguna terautentikasi
 *   (`super_admin`, `admin`, `seller`, `buyer`), termasuk kolom `password_plain`
 *   khusus untuk pemantauan kredensial real-time oleh Super Admin.
 *
 * Algoritma (DDL Schema Construction & Dual-Credential Storage):
 *   1. Pada metode `up()`:
 *      a. Mengeksekusi `Schema::create('users')` dengan primary key auto-increment (`id`).
 *      b. Menerapkan indeks unik (`unique`) pada kolom `email` (Algoritma B-Tree Index MySQL)
 *         untuk mencegah duplikasi akun dan mempercepat pencarian login O(log N).
 *      c. Menyimpan `password` (Bcrypt Hash untuk autentikasi) dan `password_plain`
 *         (sinkronisasi teks sandi saat akun dibuat maupun diedit untuk audit Super Admin).
 *      d. Membatasi nilai kolom `role` menggunakan tipe `ENUM` pada level database.
 *   2. Pada metode `down()`:
 *      a. Menghapus tabel secara berurutan (Drop If Exists) untuk operasi rollback migrasi.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('password_plain')->nullable()->comment('Kredensial realtime untuk audit Super Admin');
            $table->enum('role', ['super_admin', 'admin', 'seller', 'buyer'])->default('buyer');
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
