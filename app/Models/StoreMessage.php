<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: STORE MESSAGE (PESAN CHAT DI DALAM APLIKASI)
 * ============================================================================
 */
class StoreMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'product_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(StoreConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function toChatPayload(int $viewerId): array
    {
        $productPayload = null;
        if ($this->product) {
            $productPayload = [
                'id'              => $this->product->id,
                'name'            => $this->product->product_name,
                'price_formatted' => $this->product->formatted_price,
                'image_url'       => $this->product->all_image_urls[0] ?? asset('images/logo-hilalkraf.jpg'),
                'url'             => route('products.show', $this->product->slug),
            ];
        }

        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name ?? 'Pengguna',
            'is_mine'         => (int) $this->sender_id === $viewerId,
            'body'            => (string) $this->body,
            'time_label'      => $this->created_at?->format('H:i') ?? '',
            'date_label'      => $this->created_at?->format('d M Y') ?? '',
            'is_read'         => $this->read_at !== null,
            'product'         => $productPayload,
        ];
    }
}
