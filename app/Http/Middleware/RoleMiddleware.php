<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ============================================================================
 * MIDDLEWARE: ROLE-BASED ACCESS CONTROL (RBAC)
 * ============================================================================
 * Fungsi:
 *   Menyaring setiap HTTP Request yang masuk ke route terproteksi dan memastikan
 *   hanya pengguna dengan `role` yang sesuai (`super_admin`, `admin`, `seller`,
 *   atau `buyer`) serta berstatus aktif yang diizinkan mengakses Controller.
 *
 * Algoritma (Guard Clause & Set Membership Verification):
 *   1. Mengambil objek user dari sesi autentikasi (`$request->user()`).
 *   2. Guard Clause 1: Jika `$user` bernilai `null` (Guest) atau `!$user->is_active`,
 *      hentikan eksekusi seketika dengan HTTP 403 Forbidden.
 *   3. Guard Clause 2: Menerima variadic parameter `...$roles` dari definisi route
 *      (misal `role:super_admin,admin`) dan mengecek keanggotaan himpunan menggunakan
 *      `in_array($user->role, $roles, true)`.
 *   4. Jika cocok, teruskan request ke pipeline berikutnya (`$next($request)`);
 *      jika tidak cocok, lempar HTTP 403.
 * ============================================================================
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Langkah 1: Validasi keberadaan user dan status aktif akun
        if (! $user || ! $user->is_active) {
            abort(403, 'Akun Anda tidak aktif atau belum terautentikasi.');
        }

        // Langkah 2: Algoritma pencocokan role user terhadap daftar role yang diizinkan pada route
        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        // Langkah 3: Teruskan request ke Controller tujuan
        return $next($request);
    }
}
