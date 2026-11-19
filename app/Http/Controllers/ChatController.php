<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreConversation;
use App\Models\StoreMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: CHAT PENJUAL & PEMBELI DI DALAM APLIKASI
 * ============================================================================
 * Fungsi:
 *   Menangani komunikasi langsung di dalam aplikasi antara Pembeli dan
 *   Pemilik Toko (Siswa/Guru), terpisah dari tombol "Tanya / Konsultasi WA"
 *   yang khusus membuka WhatsApp.
 * ============================================================================
 */
class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $store = $user->store;

        $conversations = StoreConversation::forUser($user)
            ->with([
                'buyer',
                'store.owner',
                'lastProduct',
                'latestMessage.sender',
            ])
            ->withCount([
                'messages as unread_count' => fn ($q) => $q
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at'),
            ])
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->get();

        $activeConversation = null;
        if ($request->filled('conversation')) {
            $requestedId = (int) $request->input('conversation');
            $activeConversation = $conversations->firstWhere('id', $requestedId);
            if (! $activeConversation) {
                $fallback = StoreConversation::with(['buyer', 'store.owner', 'lastProduct'])->find($requestedId);
                if ($fallback) {
                    abort_unless($fallback->canAccess($user), 403);
                    $activeConversation = $fallback;
                    $conversations->prepend($fallback);
                }
            }
        } elseif ($conversations->isNotEmpty()) {
            $activeConversation = $conversations->first();
        }

        $messages = collect();
        $contextProduct = null;

        if ($activeConversation) {
            $activeConversation->markAsReadFor($user);
            $activeConversation->unread_count = 0;

            $messages = $activeConversation->messages()
                ->with(['sender', 'product'])
                ->orderBy('id')
                ->get();

            if ($request->filled('product_id')) {
                $contextProduct = Product::where('id', (int) $request->input('product_id'))
                    ->where('store_id', $activeConversation->store_id)
                    ->first();
            } elseif ($messages->isEmpty() && $activeConversation->lastProduct && (int) $activeConversation->lastProduct->store_id === (int) $activeConversation->store_id) {
                $contextProduct = $activeConversation->lastProduct;
            }
        }

        $dashboardRoute = match ($user->role) {
            'super_admin', 'admin' => route('admin.dashboard'),
            'seller'               => route('seller.dashboard'),
            default                => route('buyer.dashboard'),
        };

        return view('chat.index', compact(
            'user',
            'store',
            'conversations',
            'activeConversation',
            'messages',
            'contextProduct',
            'dashboardRoute'
        ));
    }

    public function startWithStore(Request $request, Store $store): RedirectResponse
    {
        $user = $request->user();

        if (($store->status ?? 'active') !== 'active' || ! $store->is_verified) {
            return back()->with('error', 'Toko saat ini sedang tidak aktif.');
        }

        if ((int) $store->user_id === (int) $user->id) {
            return redirect()->route('chat.index')
                ->with('success', 'Ini adalah toko Anda sendiri. Gunakan kotak masuk ini untuk membalas chat dari pembeli.');
        }

        $product = null;
        if ($request->filled('product_id')) {
            $product = Product::where('id', (int) $request->input('product_id'))
                ->where('store_id', $store->id)
                ->first();
        }

        $conversation = StoreConversation::firstOrCreate(
            ['buyer_id' => $user->id, 'store_id' => $store->id],
            [
                'last_product_id' => $product?->id,
                'last_message_at' => null,
            ]
        );

        if ($product && (int) $conversation->last_product_id !== (int) $product->id) {
            $conversation->update(['last_product_id' => $product->id]);
        }

        $params = ['conversation' => $conversation->id];
        if ($product) {
            $params['product_id'] = $product->id;
        }

        return redirect()->route('chat.index', $params);
    }

    public function startWithBuyer(Request $request, User $buyer): RedirectResponse
    {
        $user = $request->user();
        $store = $user->store;

        if (! $store) {
            return back()->with('error', 'Anda belum memiliki toko aktif untuk mengirim chat sebagai penjual.');
        }

        if ((int) $buyer->id === (int) $user->id) {
            return redirect()->route('chat.index');
        }

        $conversation = StoreConversation::firstOrCreate(
            ['buyer_id' => $buyer->id, 'store_id' => $store->id],
            ['last_message_at' => null]
        );

        return redirect()->route('chat.index', ['conversation' => $conversation->id]);
    }

    public function messages(Request $request, StoreConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->canAccess($user), 403);

        $afterId = max(0, (int) $request->input('after_id', 0));
        $conversation->markAsReadFor($user);

        $newMessages = $conversation->messages()
            ->with(['sender', 'product'])
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();

        return response()->json([
            'messages'     => $newMessages->map(fn (StoreMessage $msg) => $msg->toChatPayload($user->id))->values(),
            'unread_total' => StoreConversation::totalUnreadFor($user),
        ]);
    }

    public function sendMessage(Request $request, StoreConversation $conversation): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($conversation->canAccess($user), 403);

        $validated = $request->validate([
            'body'       => ['required', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer'],
        ], [
            'body.required' => 'Pesan tidak boleh kosong.',
            'body.max'      => 'Pesan maksimal 2.000 karakter.',
        ]);

        $cleanBody = trim((string) $validated['body']);
        if ($cleanBody === '') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pesan tidak boleh kosong.'], 422);
            }

            return back()->with('error', 'Pesan tidak boleh kosong.');
        }

        $validProductId = null;
        if (! empty($validated['product_id'])) {
            $validProductId = Product::where('id', (int) $validated['product_id'])
                ->where('store_id', $conversation->store_id)
                ->value('id');
        }

        $message = DB::transaction(function () use ($conversation, $user, $cleanBody, $validProductId) {
            $msg = StoreMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $user->id,
                'product_id'      => $validProductId,
                'body'            => $cleanBody,
            ]);

            $updates = ['last_message_at' => $msg->created_at ?? now()];
            if ($validProductId) {
                $updates['last_product_id'] = $validProductId;
            }
            $conversation->update($updates);

            return $msg;
        });

        $message->load(['sender', 'product']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message->toChatPayload($user->id),
            ], 201);
        }

        return redirect()->route('chat.index', ['conversation' => $conversation->id]);
    }
}
