<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: KOLOM KONTRAK & DURASI TAYANG IKLAN MITRA PADA TABEL CMS_CONTENTS
 * ============================================================================
 * Fungsi:
 *   Menambahkan atribut khusus manajemen Iklan Mitra Kerjasama Sekolah
 *   (contoh: BSI, PT. Mega Mentari, Daihatsu) pada tabel `cms_contents`:
 *   - `partner_name`     : Nama instansi/perusahaan mitra pengiklan.
 *   - `badge_text`       : Label kategori kerjasama iklan.
 *   - `duration_seconds` : Durasi tayang per slide iklan (detik) sebelum
 *                          bergantian/berkedip ke iklan berikutnya.
 *   - `contract_start`   : Tanggal mulai masa kontrak iklan.
 *   - `contract_end`     : Tanggal berakhir masa kontrak iklan.
 *   - `theme_color`      : Tema visual kartu banner iklan.
 *
 * Algoritma:
 *   Memeriksa keberadaan masing-masing kolom menggunakan `Schema::hasColumn`
 *   sebelum menambahkannya agar migrasi bersifat idempoten.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_contents')) {
            Schema::table('cms_contents', function (Blueprint $table) {
                if (! Schema::hasColumn('cms_contents', 'partner_name')) {
                    $table->string('partner_name', 120)->nullable()->after('title');
                }
                if (! Schema::hasColumn('cms_contents', 'badge_text')) {
                    $table->string('badge_text', 100)->nullable()->after('partner_name');
                }
                if (! Schema::hasColumn('cms_contents', 'duration_seconds')) {
                    $table->unsignedInteger('duration_seconds')->default(5)->after('link_url');
                }
                if (! Schema::hasColumn('cms_contents', 'contract_start')) {
                    $table->date('contract_start')->nullable()->after('duration_seconds');
                }
                if (! Schema::hasColumn('cms_contents', 'contract_end')) {
                    $table->date('contract_end')->nullable()->after('contract_start');
                }
                if (! Schema::hasColumn('cms_contents', 'theme_color')) {
                    $table->string('theme_color', 40)->nullable()->default('emerald')->after('contract_end');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_contents')) {
            Schema::table('cms_contents', function (Blueprint $table) {
                $cols = collect(['partner_name', 'badge_text', 'duration_seconds', 'contract_start', 'contract_end', 'theme_color'])
                    ->filter(fn ($col) => Schema::hasColumn('cms_contents', $col))
                    ->values()
                    ->all();

                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

