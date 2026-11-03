<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL ORDERS & ORDER_ITEMS (TRANSAKSI PEMBELIAN)
 * ============================================================================
 * Fungsi:
 *   Mencatat header transaksi pembelian (`orders`) per toko dan rincian item
 *   produk yang dibeli (`order_items`) oleh Pembeli (`buyer`).
 *
 * Algoritma (Master-Detail Transaction Normalization):
 *   1. Normalisasi database bentuk ketiga (3NF) dengan memisahkan tabel Master
 *      (`orders`) dan tabel Detail (`order_items`).
 *   2. Ketika pembeli melakukan checkout dari beberapa toko sekaligus, algoritma
 *      Order Splitting akan memecah pesanan berdasarkan `store_id` sehingga
 *      setiap toko siswa hanya menerima dan mengelola pesanan miliknya sendiri.
 *   3. Menyimpan `unit_price` dan `subtotal` secara historis pada `order_items`
 *      agar perubahan harga produk di masa depan tidak mengubah nilai invoice lama.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('shipping_address')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
