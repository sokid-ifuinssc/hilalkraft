{{--
============================================================================
PARTIAL: MODAL PILIH USER "LIHAT SEBAGAI" (partials/impersonate-modal.blade.php)
============================================================================
Fungsi:
  Menampilkan pemilih cepat seluruh pengguna (Pemilik Toko, Pembeli, Admin,
  dan Super Admin) lengkap dengan filter role & pencarian instan agar Super Admin
  maupun Admin dapat langsung melihat tampilan sistem sebagai user mana saja.
============================================================================
--}}
@if(isset($impersonationActor) && $impersonationActor)
    @php
        $lsUsers = $impersonatableUsers ?? collect();
        $lsCounts = [
            'all'         => $lsUsers->count(),
            'seller'      => $lsUsers->where('role', 'seller')->count(),
            'buyer'       => $lsUsers->where('role', 'buyer')->count(),
            'admin'       => $lsUsers->where('role', 'admin')->count(),
            'super_admin' => $lsUsers->where('role', 'super_admin')->count(),
        ];
    @endphp

    <div id="lihat-sebagai-modal"
         class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
         onclick="if (event.target === this) closeLihatSebagaiModal()">
        <div class="w-full max-w-2xl rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden flex flex-col max-h-[88vh]">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-[#F8FAFC]">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-9 h-9 rounded-xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-extrabold text-[#0F291E] truncate">Lihat Sebagai Pengguna</h3>
                        <p class="text-[11px] text-slate-500 truncate">
                            @if($impersonationActor->role === 'super_admin')
                                Super Admin dapat masuk melihat tampilan sebagai semua pengguna terdaftar.
                            @else
                                Admin dapat masuk melihat tampilan sebagai Pemilik Toko, Pembeli, dan sesama Admin.
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeLihatSebagaiModal()" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-500 flex items-center justify-center shrink-0" aria-label="Tutup">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Search & Role Tabs --}}
            <div class="p-4 border-b border-slate-100 space-y-3 bg-white">
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search"
                           id="ls-modal-search"
                           oninput="filterLihatSebagaiUsers()"
                           placeholder="Cari nama, email, nama toko, atau kelas/jurusan..."
                           class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] pl-10 pr-4 py-2 text-xs sm:text-sm text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                </div>

                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                    <button type="button" onclick="setLihatSebagaiRoleFilter('all', this)" class="ls-role-tab active rounded-lg px-2.5 py-1 bg-[#0B6E4F] text-white transition">
                        Semua ({{ $lsCounts['all'] }})
                    </button>
                    <button type="button" onclick="setLihatSebagaiRoleFilter('seller', this)" class="ls-role-tab rounded-lg px-2.5 py-1 bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        Pemilik Toko ({{ $lsCounts['seller'] }})
                    </button>
                    <button type="button" onclick="setLihatSebagaiRoleFilter('buyer', this)" class="ls-role-tab rounded-lg px-2.5 py-1 bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        Pembeli ({{ $lsCounts['buyer'] }})
                    </button>
                    <button type="button" onclick="setLihatSebagaiRoleFilter('admin', this)" class="ls-role-tab rounded-lg px-2.5 py-1 bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                        Admin ({{ $lsCounts['admin'] }})
                    </button>
                    @if($impersonationActor->role === 'super_admin')
                        <button type="button" onclick="setLihatSebagaiRoleFilter('super_admin', this)" class="ls-role-tab rounded-lg px-2.5 py-1 bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                            Super Admin ({{ $lsCounts['super_admin'] }})
                        </button>
                    @endif
                </div>
            </div>

            {{-- User List --}}
            <div id="ls-modal-list" class="flex-1 overflow-y-auto divide-y divide-slate-100 px-4 py-1">
                @forelse($lsUsers as $lsUser)
                    @php
                        $isCurrentSessionUser = ($lsUser->id === auth()->id());
                        $badgeStyle = match($lsUser->role) {
                            'super_admin' => 'bg-slate-900 text-white',
                            'admin'       => 'bg-blue-100 text-blue-800',
                            'seller'      => 'bg-emerald-100 text-[#0B6E4F]',
                            default       => 'bg-amber-100 text-amber-800',
                        };
                        $subInfo = collect([
                            $lsUser->store?->store_name ? 'Toko: ' . $lsUser->store->store_name : null,
                            $lsUser->class_name ? 'Kelas ' . $lsUser->class_name . ($lsUser->major_name ? ' ' . $lsUser->major_name : '') : null,
                            $lsUser->email,
                        ])->filter()->implode(' • ');
                    @endphp
                    <div class="ls-user-item py-2.5 flex items-center justify-between gap-3"
                         data-role="{{ $lsUser->role }}"
                         data-search="{{ strtolower($lsUser->name . ' ' . $lsUser->email . ' ' . ($lsUser->store?->store_name ?? '') . ' ' . ($lsUser->class_name ?? '') . ' ' . ($lsUser->major_name ?? '') . ' ' . $lsUser->role_label) }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-[#EBF9F2] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($lsUser->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-xs sm:text-sm font-bold text-[#0F291E] truncate">{{ $lsUser->name }}</p>
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $badgeStyle }}">
                                        {{ $lsUser->role_label }}
                                    </span>
                                    @if($lsUser->id === $impersonationActor->id)
                                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-600">Akun Asal</span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $subInfo }}</p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($isCurrentSessionUser)
                                <span class="inline-flex items-center gap-1 rounded-xl bg-slate-100 text-slate-500 px-3 py-1.5 text-[11px] font-bold">
                                    Sedang Aktif
                                </span>
                            @else
                                <form action="{{ route('admin.impersonate', $lsUser, false) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3 py-1.5 text-xs font-bold shadow-2xs transition active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <span>Lihat Sebagai</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-400">Tidak ada pengguna aktif yang tersedia.</p>
                @endforelse

                <p id="ls-modal-empty" class="hidden py-8 text-center text-xs text-slate-400">
                    Tidak ada pengguna yang cocok dengan pencarian Anda.
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 border-t border-slate-100 bg-[#F8FAFC] flex items-center justify-between gap-3">
                @if(session()->has('impersonator_id') || session()->has('view_as_role'))
                    <form action="{{ route('admin.impersonate.leave', [], false) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white px-3.5 py-1.5 text-xs font-bold transition shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            <span>Kembali ke Akun Asal ({{ $impersonationActor->name }})</span>
                        </button>
                    </form>
                @else
                    <span class="text-[11px] text-slate-400">Masuk sebagai: <strong>{{ $impersonationActor->name }}</strong> ({{ $impersonationActor->role_label }})</span>
                @endif
                <button type="button" onclick="closeLihatSebagaiModal()" class="rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        window._lsActiveRoleFilter = 'all';

        function openLihatSebagaiModal(defaultRole) {
            const modal = document.getElementById('lihat-sebagai-modal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (defaultRole) {
                const targetBtn = Array.from(modal.querySelectorAll('.ls-role-tab')).find(btn => (btn.getAttribute('onclick') || '').includes("'" + defaultRole + "'"));
                if (targetBtn) {
                    setLihatSebagaiRoleFilter(defaultRole, targetBtn);
                }
            }
            const input = document.getElementById('ls-modal-search');
            if (input) setTimeout(() => input.focus(), 50);
        }

        function closeLihatSebagaiModal() {
            const modal = document.getElementById('lihat-sebagai-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function setLihatSebagaiRoleFilter(role, btn) {
            window._lsActiveRoleFilter = role || 'all';
            document.querySelectorAll('.ls-role-tab').forEach(el => {
                el.classList.remove('bg-[#0B6E4F]', 'text-white', 'active');
                el.classList.add('bg-slate-100', 'text-slate-600');
            });
            if (btn) {
                btn.classList.remove('bg-slate-100', 'text-slate-600');
                btn.classList.add('bg-[#0B6E4F]', 'text-white', 'active');
            }
            filterLihatSebagaiUsers();
        }

        function filterLihatSebagaiUsers() {
            const q = (document.getElementById('ls-modal-search')?.value || '').trim().toLowerCase();
            const role = window._lsActiveRoleFilter || 'all';
            const items = document.querySelectorAll('#ls-modal-list .ls-user-item');
            let visibleCount = 0;

            items.forEach(item => {
                const matchRole = (role === 'all') || (item.dataset.role === role);
                const matchSearch = !q || (item.dataset.search || '').includes(q);
                const show = matchRole && matchSearch;
                item.classList.toggle('hidden', !show);
                if (show) visibleCount++;
            });

            const emptyEl = document.getElementById('ls-modal-empty');
            if (emptyEl) emptyEl.classList.toggle('hidden', visibleCount > 0);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeLihatSebagaiModal();
        });
    </script>
@endif
