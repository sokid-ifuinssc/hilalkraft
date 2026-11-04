<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL CMS CONTENTS (SISTEM MANAJEMEN KONTEN SUPER ADMIN)
 * ============================================================================
 * Fungsi:
 *   Menyimpan seluruh elemen konten dinamis website hilalkraft (Topbar, Hero
 *   Banner, Poin Nilai Utama, Banner Cerita Kreator, Footer, serta Kartu/Banner
 *   Pengumuman Tambahan) agar dapat diedit dan ditambahkan langsung oleh
 *   Super Admin melalui Dashboard CMS.
 * Algoritma:
 *   Menggunakan struktur hibrida Key-Value & Section-Based (`key`, `section`,
 *   `title`, `content`, `image`, `link_url`, `is_active`) sehingga mendukung
 *   pengeditan elemen bawaan halaman sekaligus penambahan blok konten baru.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_contents')) {
            Schema::create('cms_contents', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('section', 50)->default('custom_banner');
                $table->string('title', 150);
                $table->text('content')->nullable();
                $table->string('image')->nullable();
                $table->string('link_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_contents');
    }
};

