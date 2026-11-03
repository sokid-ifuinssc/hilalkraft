<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL PRODUCTS (KATALOG KARYA SISWA)
 * ============================================================================
 * Fungsi:
 *   Menyimpan data karya/produk yang diunggah oleh Pemilik Toko (Siswa),
 *   meliputi nama produk, slug URL, deskripsi, harga, stok, foto, dan kategori.
 *
 * Algoritma (Indexed Catalog Storage):
 *   1. Menghubungkan setiap produk ke toko pemiliknya melalui `store_id`
 *      dengan aturan `cascadeOnDelete()`.
 *   2. Menyimpan `price` dengan tipe `DECIMAL(12, 2)` untuk presisi aritmatika
 *      mata uang Rupiah (menghindari galat pembulatan floating-point IEEE 754).
 *   3. Menerapkan indeks B-Tree pada `slug` (unique) dan `category` (index)
 *      agar algoritma pencarian dan filter kategori berjalan cepat.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock')->default(1);
            $table->string('image');
            $table->string('category', 100)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
