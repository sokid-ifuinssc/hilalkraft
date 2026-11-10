<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'allowed_delivery_methods')) {
                $table->json('allowed_delivery_methods')->nullable()->after('is_active');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'bank_name')) {
                $table->string('bank_name', 50)->nullable()->after('contact_number');
            }
            if (! Schema::hasColumn('stores', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('stores', 'bank_account_name')) {
                $table->string('bank_account_name', 100)->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('stores', 'ewallet_name')) {
                $table->string('ewallet_name', 50)->nullable()->after('bank_account_name');
            }
            if (! Schema::hasColumn('stores', 'ewallet_account_number')) {
                $table->string('ewallet_account_number', 50)->nullable()->after('ewallet_name');
            }
            if (! Schema::hasColumn('stores', 'ewallet_account_name')) {
                $table->string('ewallet_account_name', 100)->nullable()->after('ewallet_account_number');
            }
            if (! Schema::hasColumn('stores', 'payment_instructions')) {
                $table->text('payment_instructions')->nullable()->after('ewallet_account_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'allowed_delivery_methods')) {
                $table->dropColumn('allowed_delivery_methods');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'ewallet_name',
                'ewallet_account_number',
                'ewallet_account_name',
                'payment_instructions',
            ]);
        });
    }
};

