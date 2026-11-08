<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: SOFT DELETES PADA STORES & TABEL ACTIVITY_LOGS (LOG SISTEM LENGKAP)
 * ============================================================================
 * Fungsi:
 *   1. Menambahkan kolom Soft Deletes (`deleted_at`) dan kolom `status` pada tabel
 *      `stores` agar admin/super admin dapat memisahkan & memantau:
 *      - Toko Aktif
 *      - Toko Nonaktif (Tidak aktif / belum diverifikasi)
 *      - Toko Terhapus (Soft-deleted)
 *      beserta tanggal histori pendaftaran akun, pembuatan toko, edit, dan hapus.
 *   2. Membangun tabel `activity_logs` untuk mendeteksi secara menyeluruh siapa saja
 *      yang masuk (login/logout/register) dan mengakses/melakukan aktivitas pada
 *      sistem hilalkraft (lengkap seperti pada platform HilalEdu).
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah soft deletes & status pada tabel stores
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (! Schema::hasColumn('stores', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
                if (! Schema::hasColumn('stores', 'status')) {
                    $table->string('status', 20)->default('active')->after('is_verified')
                        ->comment('active, inactive, suspended');
                }
            });
        }

        // 2. Buat tabel activity_logs
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name', 150)->nullable();
                $table->string('user_role', 50)->nullable();
                $table->string('activity_type', 50)->index()
                    ->comment('login, logout, register, store_create, store_update, store_delete, store_restore, product_create, product_update, product_delete, order_update, cms_update, access');
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('device', 120)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('url', 500)->nullable();
                $table->json('properties')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (Schema::hasColumn('stores', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('stores', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};

