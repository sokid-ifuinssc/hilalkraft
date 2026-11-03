<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * MODEL: CATEGORY (KATEGORI PRODUK DINAMIS)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `categories` untuk pengelolaan kategori karya/produk
 *   secara dinamis oleh Super Admin dan Admin.
 *
 * Algoritma:
 *   Active Record ORM dengan pemetaan kolom `name`, `slug`, dan `description`.
 * ============================================================================
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];
}

