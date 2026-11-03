{{--
============================================================================
VIEW: LOGIN MULTI-ROLE (auth/login.blade.php)
============================================================================
Fungsi:
  Menampilkan formulir autentikasi untuk Super Admin, Admin, Pemilik Toko,
  dan Pembeli.
Algoritma:
  Mengirimkan payload POST ke `route('login.attempt')` dengan proteksi token
  CSRF (`@csrf`), lalu mempertahankan nilai email (`old('email')`) apabila
  validasi kredensial gagal.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Masuk Akun — hilalkraft')

@section('content')
<div class="max-w-md mx-auto px-4 py-14">
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 shadow-sm space-y-6">
        <div class="text-center space-y-2">
            <img src="{{ asset('images/logo-hilalkraft.jpg') }}" alt="hilalkraft" class="w-16 h-16 mx-auto rounded-2xl object-contain border border-emerald-100 p-1">
            <h1 class="text-2xl font-extrabold text-slate-900">Masuk ke hilalkraft</h1>
            <p class="text-xs text-slate-500">Portal Login Super Admin, Admin, Siswa Kreator & Pembeli</p>
        </div>

        <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="nama@hilalkraft.sch.id"
                       class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi</label>
                <input type="password" name="password" required
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 hover:opacity-95 transition">
                Masuk Sekarang
            </button>
        </form>

        <p class="text-center text-xs text-slate-500">
            Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-[#059669] hover:underline">Daftar sebagai Pembeli atau Toko Siswa</a>
        </p>
    </div>
</div>
@endsection

