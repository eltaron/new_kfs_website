<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Requests\CheckoutRequest;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Services\CartService;
use Modules\Marketplace\Services\CheckoutService;
use Modules\Marketplace\Support\MarketSettings;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
    ) {
    }

    public function create(Request $request)
    {
        $cart = $this->carts->current($request->user(), $request->session());
        $summary = $this->carts->summary($cart);

        if ($summary['groups']->isEmpty()) {
            return redirect()->route('market.cart.show')->with('error', 'سلة المشتريات فارغة.');
        }

        return view('marketplace::store.checkout', [
            'summary' => $summary,
            'user' => $request->user(),
            'codEnabled' => MarketSettings::codEnabled(),
            'centers' => (array) config('marketplace.centers', []),
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        $cart = $this->carts->current($request->user(), $request->session());
        if (! $cart) {
            return redirect()->route('market.cart.show')->with('error', 'سلة المشتريات فارغة.');
        }

        $order = $this->checkout->placeOrder(
            $request->user(),
            $cart,
            $request->safe()->only(['shipping_name', 'shipping_phone', 'shipping_center', 'shipping_address', 'notes']),
            $request->validated('payment_method'),
        );

        if ($order->payment_method === MarketOrder::METHOD_COD) {
            return redirect()->route('market.orders.show', $order)
                ->with('success', 'تم تسجيل طلبك بنجاح، وسيتم الدفع عند الاستلام.');
        }

        return redirect()->route('payments.pay', ['type' => $order->getMorphClass(), 'id' => $order->getKey()]);
    }
}
