<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Services\CheckoutService;

/**
 * Buyer-side order tracking. Each buyer sees only their own orders.
 */
class OrderController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout)
    {
    }

    public function index(Request $request)
    {
        $orders = MarketOrder::query()
            ->where('user_id', $request->user()->getKey())
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('marketplace::store.orders', compact('orders'));
    }

    public function show(Request $request, MarketOrder $order)
    {
        // Storefront tracking page shows the full order: buyer only.
        abort_unless($order->user_id === $request->user()->getKey(), 404);

        $order->load(['items.vendor:id,store_name,slug', 'items.product:id,slug', 'items.review']);
        $lastTransaction = $order->transactions()->latest()->first();

        return view('marketplace::store.order', [
            'order' => $order,
            'lastTransaction' => $lastTransaction,
        ]);
    }

    public function pay(Request $request, MarketOrder $order)
    {
        abort_unless(Gate::allows('pay', $order), 404);

        $order = $this->checkout->reopenForPayment($order);

        return redirect()->route('payments.pay', ['type' => $order->getMorphClass(), 'id' => $order->getKey()]);
    }

    public function cancel(Request $request, MarketOrder $order)
    {
        abort_unless(Gate::allows('cancel', $order), 404);

        $this->checkout->cancelUnpaid($order);

        return redirect()->route('market.orders.show', $order)->with('success', 'تم إلغاء الطلب.');
    }
}
