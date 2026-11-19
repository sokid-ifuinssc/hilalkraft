<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MODEL: STORE CONVERSATION (UTAS CHAT PEMBELI <-> TOKO DI DALAM APLIKASI)
 * ============================================================================
 */
class StoreConversation extends Model
{
    protected $fillable = [
        'buyer_id',
        'store_id',
        'last_product_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function lastProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'last_product_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StoreMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(StoreMessage::class, 'conversation_id')->latestOfMany();
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        $storeId = $user->store?->id;

        return $query->where(function (Builder $q) use ($user, $storeId) {
            $q->where('buyer_id', $user->id);
            if ($storeId) {
                $q->orWhere('store_id', $storeId);
            }
        });
    }

    public function canAccess(User $user): bool
    {
        if ((int) $this->buyer_id === (int) $user->id) {
            return true;
        }

        return $user->store !== null && (int) $this->store_id === (int) $user->store->id;
    }

    public function isSellerSideFor(User $user): bool
    {
        return $user->store !== null
            && (int) $this->store_id === (int) $user->store->id
            && (int) $this->buyer_id !== (int) $user->id;
    }

    /**
     * Ringkasan identitas lawan bicara berdasarkan sisi user yang sedang melihat.
     */
    public function counterpartFor(User $user): array
    {
        if ($this->isSellerSideFor($user)) {
            $name = $this->buyer?->name ?? 'Pembeli';
            return [
                'name'            => $name,
                'subtitle'        => $this->buyer?->subtype_label ?? 'Pembeli',
                'badge'           => 'Pembeli',
                'initials'        => strtoupper(substr($name, 0, 2)),
                'is_seller_side'  => true,
                'whatsapp_url'    => null,
                'profile_url'     => null,
            ];
        }

        $storeName = $this->store?->store_name ?? 'Toko Karya Siswa';
        $ownerName = $this->store?->owner?->name;

        return [
            'name'            => $storeName,
            'subtitle'        => $ownerName ? "Pemilik: {$ownerName}" : 'Toko Siswa SMK PAL',
            'badge'           => 'Penjual',
            'initials'        => strtoupper(substr($storeName, 0, 2)),
            'is_seller_side'  => false,
            'whatsapp_url'    => $this->store?->whatsapp_url,
            'profile_url'     => $this->store ? route('stores.show', $this->store->slug) : null,
        ];
    }

    public function unreadMessagesCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsReadFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function totalUnreadFor(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        try {
            if (! Schema::hasTable('store_conversations') || ! Schema::hasTable('store_messages')) {
                return 0;
            }

            return StoreMessage::where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->whereHas('conversation', fn (Builder $q) => $q->forUser($user))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Auto-healing skema tabel chat bila kode baru ditarik di server tanpa
     * menjalankan `php artisan migrate` secara manual.
     */
    public static function ensureTablesExist(): void
    {
        try {
            $hasConv = Schema::hasTable('store_conversations');
            $hasMsg = Schema::hasTable('store_messages');
            if ($hasConv && $hasMsg) {
                return;
            }

            if (! $hasConv && Schema::hasTable('users') && Schema::hasTable('stores')) {
                Schema::create('store_conversations', function ($table) {
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

            if (! Schema::hasTable('store_messages') && Schema::hasTable('store_conversations') && Schema::hasTable('users')) {
                Schema::create('store_messages', function ($table) {
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
        } catch (\Throwable $e) {
            // Abaikan bila proses migrasi paralel sedang berjalan
        }
    }
}
