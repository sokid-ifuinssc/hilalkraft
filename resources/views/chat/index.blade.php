{{--
============================================================================
VIEW: CHAT DALAM APLIKASI PENJUAL & PEMBELI (chat/index.blade.php)
============================================================================
Fungsi:
  Menampilkan kotak masuk & ruang chat langsung di dalam aplikasi antara
  Pembeli dan Pemilik Toko (terpisah dari tombol Tanya/Konsultasi WA).
  Mendukung pengiriman pesan real-time ringan via fetch JSON maupun fallback
  form POST standar, serta kartu konteks produk yang ditanyakan.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Chat Penjual — hilalkraf')
@section('hide_footer', true)

@section('content')
@php
    $hasSelectedParam = request()->filled('conversation');
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-6 space-y-4">

    <!-- Header Ringkas -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-lg sm:text-xl font-extrabold text-[#0F291E] tracking-tight">Chat Penjual &amp; Pembeli (Aplikasi)</h1>
                <p class="text-xs text-slate-500">Diskusi pesanan, stok, dan detail karya langsung di dalam aplikasi hilalkraf.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Katalog</span>
            </a>
            <a href="{{ $dashboardRoute }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>Dashboard Saya</span>
            </a>
        </div>
    </div>

    <!-- Container Utama Chat 2 Kolom -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[560px]">

        <!-- Panel Kiri: Daftar Percakapan -->
        <aside class="lg:col-span-4 border-b lg:border-b-0 lg:border-r border-slate-200/80 flex flex-col {{ ($activeConversation && $hasSelectedParam) ? 'hidden lg:flex' : 'flex' }}">
            <div class="px-4 py-3 border-b border-slate-100 bg-[#F8FAFC] flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Daftar Percakapan</span>
                <span class="rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">{{ $conversations->count() }}</span>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 max-h-[540px]">
                @forelse($conversations as $conv)
                    @php
                        $cp = $conv->counterpartFor($user);
                        $isActive = $activeConversation && (int) $activeConversation->id === (int) $conv->id;
                        $unreadCount = (int) ($conv->unread_count ?? 0);
                        $latestMsg = $conv->latestMessage;
                    @endphp
                    <a href="{{ route('chat.index', ['conversation' => $conv->id]) }}"
                       class="flex items-start gap-3 px-4 py-3.5 transition hover:bg-[#F8FAFC] {{ $isActive ? 'bg-[#EBF9F2]/70 border-l-4 border-l-[#0B6E4F]' : '' }}">
                        <div class="w-10 h-10 rounded-full {{ $cp['is_seller_side'] ? 'bg-amber-100 text-amber-800' : 'bg-[#EBF9F2] text-[#0B6E4F]' }} flex items-center justify-center text-xs font-extrabold shrink-0">
                            {{ $cp['initials'] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $cp['name'] }}</p>
                                @if($latestMsg?->created_at)
                                    <span class="text-[10px] text-slate-400 shrink-0">{{ $latestMsg->created_at->format('H:i') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-600">{{ $cp['badge'] }}</span>
                                <span class="text-[10px] text-slate-400 truncate">{{ $cp['subtitle'] }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-1.5">
                                <p class="text-[11px] text-slate-500 truncate">
                                    @if($latestMsg)
                                        @if((int) $latestMsg->sender_id === (int) $user->id)
                                            <span class="font-semibold text-slate-700">Anda:</span>
                                        @endif
                                        {{ $latestMsg->body }}
                                    @else
                                        Mulai percakapan baru...
                                    @endif
                                </p>
                                @if($unreadCount > 0)
                                    <span class="rounded-full bg-[#0B6E4F] text-white px-2 py-0.5 text-[10px] font-extrabold shrink-0">{{ $unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-[#0F291E]">Belum Ada Percakapan</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Klik tombol <strong>Chat Penjual</strong> di halaman produk mana pun untuk memulai percakapan di aplikasi.</p>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition">
                            <span>Jelajahi Katalog</span>
                        </a>
                    </div>
                @endforelse
            </div>
        </aside>

        <!-- Panel Kanan: Ruang Chat Aktif -->
        <section class="lg:col-span-8 flex flex-col bg-[#F8FAFC] {{ ($activeConversation && ! $hasSelectedParam && $conversations->count() > 1) ? 'hidden lg:flex' : 'flex' }}">
            @if($activeConversation)
                @php
                    $activeCounterpart = $activeConversation->counterpartFor($user);
                @endphp

                <!-- Header Lawan Bicara -->
                <div class="bg-white px-4 sm:px-5 py-3.5 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <a href="{{ route('chat.index') }}" class="lg:hidden p-1.5 -ml-1 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="Semua Percakapan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div class="w-10 h-10 rounded-full {{ $activeCounterpart['is_seller_side'] ? 'bg-amber-100 text-amber-800' : 'bg-[#0B6E4F] text-white' }} flex items-center justify-center text-xs font-extrabold shrink-0">
                            {{ $activeCounterpart['initials'] }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-extrabold text-[#0F291E] truncate">{{ $activeCounterpart['name'] }}</h2>
                                <span class="rounded-full bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">{{ $activeCounterpart['badge'] }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 truncate">{{ $activeCounterpart['subtitle'] }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if(! empty($activeCounterpart['profile_url']))
                            <a href="{{ $activeCounterpart['profile_url'] }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Profil Toko</span>
                            </a>
                        @endif
                        @if(! empty($activeCounterpart['whatsapp_url']))
                            <a href="{{ $activeCounterpart['whatsapp_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-xl border border-[#25D366] bg-[#25D366]/10 px-3 py-1.5 text-[11px] font-bold text-[#0B6E4F] hover:bg-[#25D366] hover:text-white transition" title="Lanjut konsultasi lewat WhatsApp">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                <span>Tanya via WA</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Daftar Bubble Pesan -->
                <div id="chat-messages-container" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3 max-h-[400px] sm:max-h-[440px]">
                    @forelse($messages as $msg)
                        @php
                            $isMine = (int) $msg->sender_id === (int) $user->id;
                        @endphp
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $msg->id }}">
                            <div class="max-w-[82%] sm:max-w-[70%] space-y-1.5">
                                @if($msg->product)
                                    <a href="{{ route('products.show', $msg->product->slug) }}"
                                       class="flex items-center gap-2.5 rounded-xl border {{ $isMine ? 'border-[#0B6E4F]/30 bg-white' : 'border-slate-200 bg-white' }} p-2 shadow-2xs hover:border-[#0B6E4F] transition">
                                        <img src="{{ $msg->product->all_image_urls[0] ?? asset('images/logo-hilalkraf.jpg') }}"
                                             alt="{{ $msg->product->product_name }}"
                                             class="w-11 h-11 rounded-lg object-cover bg-slate-100 shrink-0"
                                             onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                                        <div class="min-w-0">
                                            <p class="text-[11px] font-bold text-[#0F291E] truncate">{{ $msg->product->product_name }}</p>
                                            <p class="text-[11px] font-extrabold text-[#0B6E4F]">{{ $msg->product->formatted_price }}</p>
                                        </div>
                                    </a>
                                @endif

                                <div class="rounded-2xl px-3.5 py-2.5 text-xs sm:text-sm leading-relaxed shadow-2xs {{ $isMine ? 'bg-[#0B6E4F] text-white rounded-br-sm' : 'bg-white border border-slate-200 text-[#0F291E] rounded-bl-sm' }}">
                                    <p class="whitespace-pre-line break-words">{{ $msg->body }}</p>
                                    <div class="mt-1 flex items-center justify-end gap-1.5 text-[10px] {{ $isMine ? 'text-emerald-100/90' : 'text-slate-400' }}">
                                        <span>{{ $msg->created_at?->format('H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div id="chat-empty-state" class="py-12 text-center space-y-2">
                            <p class="text-xs font-bold text-slate-600">Percakapan dimulai melalui aplikasi hilalkraf</p>
                            <p class="text-[11px] text-slate-400">Ketik pesan di bawah atau pilih template cepat untuk menyapa {{ $activeCounterpart['name'] }}.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Konteks Produk Terlampir & Form Kirim Pesan -->
                <div class="bg-white border-t border-slate-200/80 p-3.5 sm:p-4 space-y-3">
                    @if($contextProduct)
                        <div id="context-product-bar" class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-[#EBF9F2]/60 px-3 py-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="{{ $contextProduct->all_image_urls[0] ?? asset('images/logo-hilalkraf.jpg') }}"
                                     alt="{{ $contextProduct->product_name }}"
                                     class="w-10 h-10 rounded-lg object-cover bg-white shrink-0"
                                     onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#0B6E4F]">Menanyakan Produk:</p>
                                    <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $contextProduct->product_name }} ({{ $contextProduct->formatted_price }})</p>
                                </div>
                            </div>
                            <button type="button" onclick="clearContextProduct()" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white transition text-xs font-bold" title="Hapus lampiran produk">
                                &times;
                            </button>
                        </div>
                    @endif

                    @if(! $activeCounterpart['is_seller_side'])
                        <div class="flex flex-wrap gap-1.5">
                            @foreach([
                                'Halo kak, apakah produk ini masih tersedia?',
                                'Apakah bisa diambil di sekolah / stand hari ini?',
                                'Boleh info estimasi pengemasan pesanannya kak?',
                            ] as $quickReply)
                                <button type="button"
                                        onclick="insertQuickReply({{ json_encode($quickReply) }})"
                                        class="rounded-full border border-slate-200 bg-[#F8FAFC] hover:border-[#0B6E4F] hover:text-[#0B6E4F] px-3 py-1 text-[11px] font-semibold text-slate-600 transition">
                                    {{ $quickReply }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <form id="chat-send-form" action="{{ route('chat.send', $activeConversation) }}" method="POST" class="flex items-end gap-2">
                        @csrf
                        <input type="hidden" name="product_id" id="chat-product-id" value="{{ $contextProduct?->id ?? '' }}">
                        <textarea name="body"
                                  id="chat-body-input"
                                  rows="2"
                                  required
                                  maxlength="2000"
                                  placeholder="Tulis pesan Anda untuk {{ $activeCounterpart['name'] }}..."
                                  class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-xs sm:text-sm text-[#0F291E] placeholder-slate-400 focus:bg-white focus:border-[#0B6E4F] focus:outline-none resize-none"></textarea>
                        <button type="submit"
                                id="chat-send-btn"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-4 sm:px-5 py-3 text-xs sm:text-sm font-extrabold transition shadow-xs shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span>Kirim</span>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-white border border-slate-200 text-[#0B6E4F] flex items-center justify-center shadow-2xs">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h2 class="text-sm font-extrabold text-[#0F291E]">Pilih Percakapan</h2>
                    <p class="text-xs text-slate-500 max-w-sm leading-relaxed">Pilih salah satu obrolan di sebelah kiri atau klik tombol <strong>Chat Penjual</strong> pada halaman karya siswa untuk mulai berkomunikasi di dalam aplikasi.</p>
                </div>
            @endif
        </section>
    </div>
</div>

@if($activeConversation)
<script>
    const chatContainer = document.getElementById('chat-messages-container');
    const chatForm = document.getElementById('chat-send-form');
    const chatInput = document.getElementById('chat-body-input');
    const chatProductInput = document.getElementById('chat-product-id');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const pollUrl = @json(route('chat.messages', $activeConversation));
    const sendUrl = @json(route('chat.send', $activeConversation));
    const csrfToken = @json(csrf_token());
    let lastMessageId = {{ (int) ($messages->last()?->id ?? 0) }};

    function scrollChatToBottom() {
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    function insertQuickReply(text) {
        if (!chatInput) return;
        chatInput.value = text;
        chatInput.focus();
    }

    function clearContextProduct() {
        if (chatProductInput) chatProductInput.value = '';
        const bar = document.getElementById('context-product-bar');
        if (bar) bar.remove();
    }

    function appendMessageSafe(msg) {
        if (!chatContainer || !msg || !msg.id) return;
        if (chatContainer.querySelector(`[data-message-id="${msg.id}"]`)) return;

        const emptyState = document.getElementById('chat-empty-state');
        if (emptyState) emptyState.remove();

        const row = document.createElement('div');
        row.className = 'flex ' + (msg.is_mine ? 'justify-end' : 'justify-start');
        row.setAttribute('data-message-id', String(msg.id));

        const col = document.createElement('div');
        col.className = 'max-w-[82%] sm:max-w-[70%] space-y-1.5';

        if (msg.product) {
            const pLink = document.createElement('a');
            pLink.href = msg.product.url;
            pLink.className = 'flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white p-2 shadow-2xs hover:border-[#0B6E4F] transition';

            const pImg = document.createElement('img');
            pImg.src = msg.product.image_url;
            pImg.alt = msg.product.name;
            pImg.className = 'w-11 h-11 rounded-lg object-cover bg-slate-100 shrink-0';

            const pInfo = document.createElement('div');
            pInfo.className = 'min-w-0';

            const pName = document.createElement('p');
            pName.className = 'text-[11px] font-bold text-[#0F291E] truncate';
            pName.textContent = msg.product.name;

            const pPrice = document.createElement('p');
            pPrice.className = 'text-[11px] font-extrabold text-[#0B6E4F]';
            pPrice.textContent = msg.product.price_formatted;

            pInfo.appendChild(pName);
            pInfo.appendChild(pPrice);
            pLink.appendChild(pImg);
            pLink.appendChild(pInfo);
            col.appendChild(pLink);
        }

        const bubble = document.createElement('div');
        bubble.className = 'rounded-2xl px-3.5 py-2.5 text-xs sm:text-sm leading-relaxed shadow-2xs ' +
            (msg.is_mine
                ? 'bg-[#0B6E4F] text-white rounded-br-sm'
                : 'bg-white border border-slate-200 text-[#0F291E] rounded-bl-sm');

        const pBody = document.createElement('p');
        pBody.className = 'whitespace-pre-line break-words';
        pBody.textContent = msg.body;

        const meta = document.createElement('div');
        meta.className = 'mt-1 flex items-center justify-end gap-1.5 text-[10px] ' +
            (msg.is_mine ? 'text-emerald-100/90' : 'text-slate-400');
        const timeSpan = document.createElement('span');
        timeSpan.textContent = msg.time_label || '';
        meta.appendChild(timeSpan);

        bubble.appendChild(pBody);
        bubble.appendChild(meta);
        col.appendChild(bubble);
        row.appendChild(col);
        chatContainer.appendChild(row);

        if (Number(msg.id) > lastMessageId) {
            lastMessageId = Number(msg.id);
        }
        scrollChatToBottom();
    }

    if (chatInput && chatForm) {
        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.requestSubmit();
            }
        });

        chatForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const text = chatInput.value.trim();
            if (!text) return;

            if (chatSendBtn) chatSendBtn.disabled = true;
            try {
                const formData = new FormData();
                formData.append('body', text);
                if (chatProductInput && chatProductInput.value) {
                    formData.append('product_id', chatProductInput.value);
                }

                const res = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data && data.message) {
                        appendMessageSafe(data.message);
                    }
                    chatInput.value = '';
                    clearContextProduct();
                } else {
                    chatForm.submit();
                }
            } catch (err) {
                chatForm.submit();
            } finally {
                if (chatSendBtn) chatSendBtn.disabled = false;
                chatInput.focus();
            }
        });
    }

    async function pollNewMessages() {
        if (document.hidden) return;
        try {
            const res = await fetch(`${pollUrl}?after_id=${lastMessageId}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data && Array.isArray(data.messages)) {
                data.messages.forEach(appendMessageSafe);
            }
        } catch (err) {
            // Abaikan error jaringan sementara saat polling
        }
    }

    document.addEventListener('DOMContentLoaded', scrollChatToBottom);
    setInterval(pollNewMessages, 4500);
</script>
@endif
@endsection
