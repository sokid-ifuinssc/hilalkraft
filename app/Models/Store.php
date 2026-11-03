<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODEL: STORE (TOKO KARYA SISWA, GURU & JURUSAN)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `stores` yang menghubungkan Pemilik Toko (`User`
 *   dari kalangan Siswa, Guru, maupun Jurusan) dengan katalog produk (`Product`)
 *   dan pesanan masuk (`Order`), serta menyediakan generator tautan WhatsApp.
 *
 * Algoritma:
 *   1. `getWhatsappUrlAttribute()`: Regex Sanitasi Non-Digit + Konversi Prefix '0' ke '62'.
 *   2. `getSellerTypeLabelAttribute()`: Pattern Matching (`match`) untuk mengubah
 *      kode `seller_type` (`siswa`, `guru`, `jurusan`) menjadi label tampilan resmi.
 * ============================================================================
 */
class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'seller_type',
        'student_class',
        'description',
        'contact_number',
        'logo',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getWhatsappUrlAttribute(): string
    {
        $clean = preg_replace('/[^0-9]/', '', $this->contact_number);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        return "https://wa.me/{$clean}";
    }

    public function getSellerTypeLabelAttribute(): string
    {
        return match ($this->seller_type ?? 'siswa') {
            'guru'    => 'Toko Guru / Pembina',
            'jurusan' => 'Toko Unit Jurusan',
            default   => 'Toko Siswa',
        };
    }
}
