<?php

namespace App\Http\Controllers;

use App\Models\ClassMajor;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: AUTH CONTROLLER (AUTENTIKASI & REGISTRASI MULTI-ROLE + TOKO)
 * ============================================================================
 * Fungsi:
 *   Mengelola proses Login multi-role dan Registrasi publik baik untuk Pembeli
 *   (`buyer`) maupun Pemilik Toko (`seller` dari kalangan Siswa, Guru, atau Jurusan).
 *
 * Algoritma:
 *   1. Pada `showRegister()`: Memuat daftar Kelas & Jurusan aktif dari tabel
 *      `class_majors` untuk dipilih oleh pendaftar toko (Siswa, Guru, atau Jurusan).
 *   2. Pada `register()`: Mengeksekusi transaksi atomik `DB::transaction()`
 *      untuk membuat akun `User` (dengan sinkronisasi `password_plain`) dan
 *      membuat profil `Store` lengkap dengan `seller_type` (`siswa`, `guru`, `jurusan`).
 * ============================================================================
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = $request->user();

            return match ($user->role) {
                'super_admin', 'admin' => redirect()->intended(route('admin.dashboard')),
                'seller'               => redirect()->intended(route('seller.dashboard')),
                default                => redirect()->intended(route('buyer.dashboard')),
            };
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function showRegister(): View
    {
        $classMajors = ClassMajor::where('is_active', true)
            ->orderBy('class_name')
            ->orderBy('major_name')
            ->get();

        return view('auth.register', compact('classMajors'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'         => ['required', 'string', 'max:20'],
            'password'      => ['required', 'confirmed', 'min:6'],
            'role'          => ['required', 'in:seller,buyer'],
            'seller_type'   => ['required_if:role,seller', 'nullable', 'in:siswa,guru,jurusan'],
            'store_name'    => ['required_if:role,seller', 'nullable', 'string', 'max:120'],
            'student_class' => ['required_if:role,seller', 'nullable', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'           => $validated['name'],
                'email'          => $validated['email'],
                'phone'          => $validated['phone'],
                'password'       => Hash::make($validated['password']),
                'password_plain' => $validated['password'],
                'role'           => $validated['role'],
                'is_active'      => true,
            ]);

            if ($validated['role'] === 'seller') {
                Store::create([
                    'user_id'        => $user->id,
                    'store_name'     => $validated['store_name'],
                    'slug'           => Str::slug($validated['store_name']) . '-' . Str::lower(Str::random(4)),
                    'seller_type'    => $validated['seller_type'] ?? 'siswa',
                    'student_class'  => $validated['student_class'],
                    'description'    => $validated['description'] ?? 'Toko Resmi SMK Plus Al Hilal',
                    'contact_number' => $validated['phone'],
                    'is_verified'    => false,
                ]);
            }

            return $user;
        });

        Auth::login($user);

        return $user->role === 'seller'
            ? redirect()->route('seller.dashboard')->with('success', 'Akun Toko berhasil dibuat! Menunggu verifikasi Admin.')
            : redirect()->route('home')->with('success', 'Selamat datang di hilalkraft!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
