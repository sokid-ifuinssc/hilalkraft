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
 * CONTROLLER: AUTH CONTROLLER (REGISTRASI PROFIL LENGKAP TOKO & 4 TIPE PENGUNJUNG)
 * ============================================================================
 * Fungsi:
 *   Mengelola autentikasi dan registrasi dengan validasi profil lengkap:
 *   A. Pemilik Toko (`role = seller`, Satu Toko Satu Akun):
 *      - Siswa (`siswa`): wajib Nama, NIS/NISN, Kelas (DB), Jurusan (DB),
 *        Alamat Lengkap (Desa, Kecamatan, Kabupaten, Kode Pos), Nomor HP, Nama Toko.
 *      - Guru (`guru`): wajib Nama, NUPTK, Alamat Lengkap (Desa, Kecamatan,
 *        Kabupaten, Kode Pos), Nomor HP, Nama Toko (`is_teacher = true`).
 *   B. Pengunjung / Pembeli (`role = buyer`):
 *      1. `pelajar`: Nama, NIS/NISN, Asal Sekolah, Kelas, Alamat Lengkap, Nomor HP.
 *      2. `keluarga`: Nama, NIK, Alamat Lengkap, Nomor HP, Nama Siswa (Keluarga),
 *         Kelas (DB) & Jurusan (DB).
 *      3. `alumni`: Nama, NIK, Nomor HP, Alamat Lengkap, Tahun Lulus.
 *      4. `umum`: Nama, NIK, Alamat Lengkap, Nomor HP.
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
                default                => redirect()->intended(route('home')),
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

        // Daftar Kelas unik & Jurusan unik dari database untuk dropdown terpisah
        $classList = $classMajors->pluck('class_name')->unique()->values();
        $majorList = $classMajors->pluck('major_name')->unique()->values();

        return view('auth.register', compact('classMajors', 'classList', 'majorList'));
    }

    /**
     * Fungsi: Memvalidasi dan menyimpan registrasi akun Pemilik Toko maupun 4 Kategori Pengunjung.
     * Algoritma (Context-Sensitive Validation & 1-to-1 Store Provisioning):
     *   1. Memeriksa `role` (`buyer` vs `seller`) dan sub-kategorinya (`buyer_type` vs `seller_type`).
     *   2. Menerapkan aturan validasi wajib yang presisi sesuai matriks kategori pendaftar.
     *   3. Menyimpan data ke tabel `users` dan (jika `seller`) membuat tepat 1 toko di `stores`.
     */
    public function register(Request $request): RedirectResponse
    {
        $role = $request->input('role', 'buyer');
        $sellerType = $request->input('seller_type', 'siswa');
        $buyerType = $request->input('buyer_type', 'pelajar');

        // Aturan wajib dasar untuk semua pendaftar (Nama, Email, Password, Alamat Lengkap, No HP)
        $rules = [
            'name'           => ['required', 'string', 'max:120'],
            'email'          => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'          => ['required', 'string', 'max:25'],
            'desa'           => ['required', 'string', 'max:100'],
            'kecamatan'      => ['required', 'string', 'max:100'],
            'kabupaten'      => ['required', 'string', 'max:100'],
            'kode_pos'       => ['required', 'string', 'max:15'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'password'       => ['required', 'confirmed', 'min:6'],
            'role'           => ['required', 'in:seller,buyer'],
        ];

        if ($role === 'seller') {
            $rules['seller_type'] = ['required', 'in:siswa,guru'];
            $rules['store_name']  = ['required', 'string', 'max:120'];
            $rules['description'] = ['nullable', 'string', 'max:500'];

            if ($sellerType === 'siswa') {
                $rules['nis_nisn']   = ['required', 'string', 'max:30'];
                $rules['class_name'] = ['required', 'string', 'max:50'];
                $rules['major_name'] = ['required', 'string', 'max:120'];
            } else {
                // Guru
                $rules['nuptk']      = ['required', 'string', 'max:30'];
                $rules['major_name'] = ['nullable', 'string', 'max:120'];
            }
        } else {
            // Pengunjung / Pembeli (4 Kategori)
            $rules['buyer_type'] = ['required', 'in:pelajar,keluarga,alumni,umum'];

            if ($buyerType === 'pelajar') {
                $rules['nis_nisn']      = ['required', 'string', 'max:30'];
                $rules['school_origin'] = ['required', 'string', 'max:150'];
                $rules['class_name']    = ['required', 'string', 'max:50'];
            } elseif ($buyerType === 'keluarga') {
                $rules['nik']                  = ['required', 'string', 'max:30'];
                $rules['family_student_name']  = ['required', 'string', 'max:120'];
                $rules['family_student_class'] = ['required', 'string', 'max:50'];
                $rules['family_student_major'] = ['required', 'string', 'max:120'];
            } elseif ($buyerType === 'alumni') {
                $rules['nik']             = ['required', 'string', 'max:30'];
                $rules['graduation_year'] = ['required', 'string', 'max:10'];
            } else {
                // Pengunjung Umum
                $rules['nik'] = ['required', 'string', 'max:30'];
            }
        }

        $validated = $request->validate($rules);

        $user = DB::transaction(function () use ($validated, $role, $sellerType, $buyerType) {
            $subtype = $role === 'seller' ? $sellerType : $buyerType;
            $isTeacher = ($role === 'seller' && $sellerType === 'guru');

            $user = User::create([
                'name'                 => $validated['name'],
                'email'                => $validated['email'],
                'phone'                => $validated['phone'],
                'password'             => Hash::make($validated['password']),
                'password_plain'       => $validated['password'],
                'role'                 => $role,
                'user_subtype'         => $subtype,
                'is_teacher'           => $isTeacher,
                'nis_nisn'             => $validated['nis_nisn'] ?? null,
                'nuptk'                => $validated['nuptk'] ?? null,
                'nik'                  => $validated['nik'] ?? null,
                'class_name'           => $validated['class_name'] ?? null,
                'major_name'           => $validated['major_name'] ?? null,
                'school_origin'        => $validated['school_origin'] ?? null,
                'family_student_name'  => $validated['family_student_name'] ?? null,
                'family_student_class' => $validated['family_student_class'] ?? null,
                'family_student_major' => $validated['family_student_major'] ?? null,
                'graduation_year'      => $validated['graduation_year'] ?? null,
                'desa'                 => $validated['desa'],
                'kecamatan'            => $validated['kecamatan'],
                'kabupaten'            => $validated['kabupaten'],
                'kode_pos'             => $validated['kode_pos'],
                'address_detail'       => $validated['address_detail'] ?? null,
                'is_active'            => true,
            ]);

            // Satu Toko Satu Akun (1-to-1) untuk Siswa maupun Guru
            if ($role === 'seller') {
                $studentClassLabel = $sellerType === 'siswa'
                    ? trim(($validated['class_name'] ?? '') . ' - ' . ($validated['major_name'] ?? ''))
                    : 'Guru / Pembina ' . ($validated['major_name'] ?? 'SMK Plus Al Hilal');

                Store::create([
                    'user_id'        => $user->id,
                    'store_name'     => $validated['store_name'],
                    'slug'           => Str::slug($validated['store_name']) . '-' . Str::lower(Str::random(4)),
                    'seller_type'    => $sellerType,
                    'student_class'  => $studentClassLabel,
                    'description'    => $validated['description'] ?? 'Toko Resmi ' . ucfirst($sellerType) . ' SMK Plus Al Hilal',
                    'contact_number' => $validated['phone'],
                    'is_verified'    => false,
                ]);
            }

            return $user;
        });

        Auth::login($user);

        return $user->role === 'seller'
            ? redirect()->route('seller.dashboard')->with('success', 'Akun & Profil Lengkap Toko berhasil dibuat (Satu Toko Satu Akun)! Menunggu verifikasi Admin.')
            : redirect()->route('home')->with('success', 'Selamat datang di hilalkraft! Akun pengunjung Anda telah aktif dan dapat memberikan komentar serta berbelanja.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
