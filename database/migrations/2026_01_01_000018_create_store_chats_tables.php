<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRASI: CHAT PENJUAL & PEMBELI DI DALAM APLIKASI (store_conversations & store_messages)
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_conversations')) {
            Schema::create('store_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('last_product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->unique(['buyer_id', 'store_id']);
                $table->index('last_message_at');
            });
        }

        if (! Schema::hasTable('store_messages')) {
            Schema::create('store_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('store_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'id']);
                $table->index(['sender_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_messages');
        Schema::dropIfExists('store_conversations');
    }
};
