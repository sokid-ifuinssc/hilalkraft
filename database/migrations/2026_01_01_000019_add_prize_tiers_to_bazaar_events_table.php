<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRASI: TAMBAHKAN DAFTAR HADIAH POIN PENGUNJUNG (`prize_tiers`) KE `bazaar_events`
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bazaar_events') && ! Schema::hasColumn('bazaar_events', 'prize_tiers')) {
            Schema::table('bazaar_events', function (Blueprint $table) {
                $table->json('prize_tiers')->nullable()->after('allow_self_register');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bazaar_events') && Schema::hasColumn('bazaar_events', 'prize_tiers')) {
            Schema::table('bazaar_events', function (Blueprint $table) {
                $table->dropColumn('prize_tiers');
            });
        }
    }
};
