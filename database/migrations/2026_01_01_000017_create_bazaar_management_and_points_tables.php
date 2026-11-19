<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * MIGRASI: PENGELOLAAN PESERTA TOKO, BARCODE KEHADIRAN, POIN PEMBELI & DOORPRIZE BAZAR
 * ============================================================================
 * Menambahkan:
 *   1. Kolom token barcode kehadiran (`checkin_token`, `short_code`), bobot poin
 *      (`points_checkin`, `points_stand_visit`, `points_transaction`), serta
 *      kebijakan pengajuan toko (`allow_self_register`) pada tabel `bazaar_events`.
 *   2. Tabel `bazaar_store_participants` untuk mengelola toko peserta yang dipilih
 *      Admin/Super Admin maupun yang diajukan oleh toko (`pending`, `approved`, `rejected`),
 *      beserta kode/token QR khusus masing-masing stand toko.
 *   3. Tabel `bazaar_visitor_points` untuk mencatat poin pengunjung dari:
 *      - Hadir di acara bazar (scan QR pintu masuk -> misal +50 poin)
 *      - Kunjungan ke stand toko peserta (scan QR stand -> misal +20 poin)
 *      - Transaksi belanja di acara bazar (otomatis -> misal +30 poin)
 *   4. Tabel `bazaar_doorprize_winners` untuk menetapkan pemenang doorprize panitia
 *      berdasarkan peringkat poin tertinggi di setiap acara bazar.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bazaar_events')) {
            Schema::table('bazaar_events', function (Blueprint $table) {
                if (! Schema::hasColumn('bazaar_events', 'checkin_token')) {
                    $table->string('checkin_token', 64)->nullable();
                }
                if (! Schema::hasColumn('bazaar_events', 'short_code')) {
                    $table->string('short_code', 20)->nullable();
                }
                if (! Schema::hasColumn('bazaar_events', 'points_checkin')) {
                    $table->unsignedInteger('points_checkin')->default(50);
                }
                if (! Schema::hasColumn('bazaar_events', 'points_stand_visit')) {
                    $table->unsignedInteger('points_stand_visit')->default(20);
                }
                if (! Schema::hasColumn('bazaar_events', 'points_transaction')) {
                    $table->unsignedInteger('points_transaction')->default(30);
                }
                if (! Schema::hasColumn('bazaar_events', 'allow_self_register')) {
                    $table->boolean('allow_self_register')->default(true);
                }
            });

            // Isi token & kode pendek bagi event bazar yang sudah ada sebelumnya
            $existingEvents = DB::table('bazaar_events')->select('id', 'checkin_token', 'short_code')->get();
            foreach ($existingEvents as $ev) {
                $updates = [];
                if (empty($ev->checkin_token)) {
                    $updates['checkin_token'] = Str::random(24);
                }
                if (empty($ev->short_code)) {
                    $updates['short_code'] = 'BZ-' . Str::upper(Str::random(6));
                }
                if (! empty($updates)) {
                    DB::table('bazaar_events')->where('id', $ev->id)->update($updates);
                }
            }
        }

        if (! Schema::hasTable('bazaar_store_participants')) {
            Schema::create('bazaar_store_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bazaar_event_id')->constrained('bazaar_events')->cascadeOnDelete();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->string('stand_number', 30)->nullable();
                $table->string('stand_token', 64)->unique();
                $table->string('stand_code', 20)->unique();
                $table->string('status', 20)->default('pending'); // pending | approved | rejected
                $table->string('notes', 255)->nullable();
                $table->string('rejection_reason', 255)->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['bazaar_event_id', 'store_id'], 'bazaar_event_store_unique');
            });
        }

        if (! Schema::hasTable('bazaar_visitor_points')) {
            Schema::create('bazaar_visitor_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bazaar_event_id')->constrained('bazaar_events')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('activity_type', 30); // event_checkin | stand_visit | bazaar_transaction
                $table->integer('points')->default(0);
                $table->string('description', 255)->nullable();
                $table->timestamps();

                $table->index(['bazaar_event_id', 'user_id', 'activity_type'], 'bz_vp_event_user_type_idx');
                $table->index(['bazaar_event_id', 'store_id', 'activity_type'], 'bz_vp_event_store_type_idx');
            });
        }

        if (! Schema::hasTable('bazaar_doorprize_winners')) {
            Schema::create('bazaar_doorprize_winners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bazaar_event_id')->constrained('bazaar_events')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('prize_name', 120)->default('Doorprize Pengunjung Terbaik');
                $table->unsignedInteger('rank_at_award')->nullable();
                $table->unsignedInteger('total_points_at_award')->default(0);
                $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('awarded_at')->nullable();
                $table->timestamps();

                $table->unique(['bazaar_event_id', 'user_id'], 'bz_doorprize_event_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bazaar_doorprize_winners');
        Schema::dropIfExists('bazaar_visitor_points');
        Schema::dropIfExists('bazaar_store_participants');

        if (Schema::hasTable('bazaar_events')) {
            Schema::table('bazaar_events', function (Blueprint $table) {
                foreach (['checkin_token', 'short_code', 'points_checkin', 'points_stand_visit', 'points_transaction', 'allow_self_register'] as $col) {
                    if (Schema::hasColumn('bazaar_events', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
