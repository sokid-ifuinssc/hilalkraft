<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: PROGRES PESANAN (PESAN-KEMAS-KIRIM-SAMPAI), RATING BINTANG,
 *            SKEMA DISKON BAZAR/EVENT SMK & DISKON JENIS PEMBELI
 * ============================================================================
 * Fungsi:
 *   1. Menambahkan pengaturan diskon per jenis pembeli pada tabel `stores`
 *      (`discount_pelajar`, `discount_keluarga`, `discount_alumni`,
 *      `discount_umum`, `discount_bazar_extra`) yang dapat diatur oleh Penjual.
 *   2. Membuat tabel `bazaar_events` untuk skema acara Bazar / Event SMK
 *      agar pembeli yang berbelanja dan hadir pada acara bazar mendapatkan
 *      potongan harga khusus.
 *   3. Menambahkan kolom pelacakan progres barang 4 tahap pada tabel `orders`
 *      (`progress_status`: `pesan`, `kemas`, `kirim`, `sampai`, `dibatalkan`),
 *      rincian potongan diskon, serta rating bintang (`buyer_rating` 1..5 &
 *      `buyer_review`) dari pembeli setelah transaksi.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Kolom Pengaturan Diskon Jenis Pembeli & Bazar pada Tabel Stores
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (! Schema::hasColumn('stores', 'discount_pelajar')) {
                    $table->unsignedTinyInteger('discount_pelajar')->default(10)->after('views_count');
                }
                if (! Schema::hasColumn('stores', 'discount_keluarga')) {
                    $table->unsignedTinyInteger('discount_keluarga')->default(15)->after('discount_pelajar');
                }
                if (! Schema::hasColumn('stores', 'discount_alumni')) {
                    $table->unsignedTinyInteger('discount_alumni')->default(10)->after('discount_keluarga');
                }
                if (! Schema::hasColumn('stores', 'discount_umum')) {
                    $table->unsignedTinyInteger('discount_umum')->default(0)->after('discount_alumni');
                }
                if (! Schema::hasColumn('stores', 'discount_bazar_extra')) {
                    $table->unsignedTinyInteger('discount_bazar_extra')->default(5)->after('discount_umum');
                }
            });
        }

        // 2. Tabel Skema Acara Bazar & Event SMK (bazaar_events)
        if (! Schema::hasTable('bazaar_events')) {
            Schema::create('bazaar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('voucher_code', 50)->unique();
                $table->text('description')->nullable();
                $table->string('location', 150)->nullable();
                $table->unsignedTinyInteger('discount_percent')->default(10);
                $table->decimal('max_discount_amount', 12, 2)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Kolom Progres Pesanan (Pesan -> Kemas -> Kirim -> Sampai), Diskon & Rating pada Tabel Orders
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'progress_status')) {
                    $table->string('progress_status', 30)->default('pesan')->after('status');
                }
                if (! Schema::hasColumn('orders', 'progress_note')) {
                    $table->string('progress_note', 255)->nullable()->after('progress_status');
                }
                if (! Schema::hasColumn('orders', 'subtotal_price')) {
                    $table->decimal('subtotal_price', 12, 2)->default(0)->after('buyer_id');
                }
                if (! Schema::hasColumn('orders', 'buyer_type_discount_percent')) {
                    $table->unsignedTinyInteger('buyer_type_discount_percent')->default(0)->after('subtotal_price');
                }
                if (! Schema::hasColumn('orders', 'buyer_type_discount_amount')) {
                    $table->decimal('buyer_type_discount_amount', 12, 2)->default(0)->after('buyer_type_discount_percent');
                }
                if (! Schema::hasColumn('orders', 'bazaar_event_id')) {
                    $table->unsignedBigInteger('bazaar_event_id')->nullable()->after('buyer_type_discount_amount');
                }
                if (! Schema::hasColumn('orders', 'bazaar_voucher_code')) {
                    $table->string('bazaar_voucher_code', 50)->nullable()->after('bazaar_event_id');
                }
                if (! Schema::hasColumn('orders', 'bazaar_discount_percent')) {
                    $table->unsignedTinyInteger('bazaar_discount_percent')->default(0)->after('bazaar_voucher_code');
                }
                if (! Schema::hasColumn('orders', 'bazaar_discount_amount')) {
                    $table->decimal('bazaar_discount_amount', 12, 2)->default(0)->after('bazaar_discount_percent');
                }
                if (! Schema::hasColumn('orders', 'is_bazaar_offline')) {
                    $table->boolean('is_bazaar_offline')->default(false)->after('bazaar_discount_amount');
                }
                if (! Schema::hasColumn('orders', 'buyer_rating')) {
                    $table->unsignedTinyInteger('buyer_rating')->nullable()->after('shipping_address');
                }
                if (! Schema::hasColumn('orders', 'buyer_review')) {
                    $table->text('buyer_review')->nullable()->after('buyer_rating');
                }
                if (! Schema::hasColumn('orders', 'rated_at')) {
                    $table->timestamp('rated_at')->nullable()->after('buyer_review');
                }
                if (! Schema::hasColumn('orders', 'packed_at')) {
                    $table->timestamp('packed_at')->nullable()->after('rated_at');
                }
                if (! Schema::hasColumn('orders', 'shipped_at')) {
                    $table->timestamp('shipped_at')->nullable()->after('packed_at');
                }
                if (! Schema::hasColumn('orders', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('shipped_at');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_events');
    }
};

