<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: ADD IP_ADDRESS TO PRODUCT_COMMENTS TABLE
 * ============================================================================
 * Fungsi:
 *   Menambahkan kolom `ip_address` pada tabel `product_comments` agar admin &
 *   super admin dapat mengaudit alamat IP akun yang berkomentar dan mendeteksi
 *   apakah ada keterhubungan jaringan/perangkat antara pengulas dengan pemilik toko.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_comments')) {
            Schema::table('product_comments', function (Blueprint $table) {
                if (! Schema::hasColumn('product_comments', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable()->after('rating')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_comments')) {
            Schema::table('product_comments', function (Blueprint $table) {
                if (Schema::hasColumn('product_comments', 'ip_address')) {
                    $table->dropColumn('ip_address');
                }
            });
        }
    }
};
