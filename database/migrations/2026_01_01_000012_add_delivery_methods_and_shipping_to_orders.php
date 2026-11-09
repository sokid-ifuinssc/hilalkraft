<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: METODE PENGIRIMAN & TRANSAKSI (DATANG KE SEKOLAH, DELIVERY LOKAL,
 *            DAN TRANSFER ANTAR DENGAN ESTIMASI ONGKIR)
 * ============================================================================
 * Skema Transaksi Penjualan:
 *   1. `pickup` (Transaksi Langsung / Datang ke Sekolah):
 *      - Transaksi dilaksanakan di lingkungan SMK Plus Al Hilal.
 *      - Ongkir: Rp 0 (Gratis).
 *      - Proses: Dipesan -> Dikemas -> Diterima (3 Tahap).
 *   2. `delivery_lokal` (Delivery / Antar Langsung):
 *      - Khusus Kabupaten Cirebon, dibatasi 3 kecamatan:
 *        Arjawinangun, Susukan, Kaliwedi.
 *      - Ongkir: Flat Rp 5.000.
 *      - Proses: Dipesan -> Dikemas -> Dikirim -> Diterima (4 Tahap).
 *   3. `shipping_transfer` (Transfer Antar):
 *      - Pengiriman melalui ekspedisi ke luar 3 kecamatan / luar kota.
 *      - Ongkir: Disesuaikan dengan estimasi lokasi pengiriman.
 *      - Proses: Dipesan -> Dikemas -> Dikirim -> Diterima (4 Tahap).
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'delivery_method')) {
                    $table->string('delivery_method', 30)->default('pickup')->after('status')
                        ->comment('pickup, delivery_lokal, shipping_transfer');
                }
                if (! Schema::hasColumn('orders', 'shipping_cost')) {
                    $table->decimal('shipping_cost', 12, 2)->default(0)->after('delivery_method');
                }
                if (! Schema::hasColumn('orders', 'destination_kabupaten')) {
                    $table->string('destination_kabupaten', 100)->nullable()->after('shipping_address');
                }
                if (! Schema::hasColumn('orders', 'destination_kecamatan')) {
                    $table->string('destination_kecamatan', 100)->nullable()->after('destination_kabupaten');
                }
                if (! Schema::hasColumn('orders', 'destination_desa')) {
                    $table->string('destination_desa', 100)->nullable()->after('destination_kecamatan');
                }
                if (! Schema::hasColumn('orders', 'courier_name')) {
                    $table->string('courier_name', 100)->nullable()->after('destination_desa');
                }
                if (! Schema::hasColumn('orders', 'tracking_number')) {
                    $table->string('tracking_number', 100)->nullable()->after('courier_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $cols = ['delivery_method', 'shipping_cost', 'destination_kabupaten', 'destination_kecamatan', 'destination_desa', 'courier_name', 'tracking_number'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

