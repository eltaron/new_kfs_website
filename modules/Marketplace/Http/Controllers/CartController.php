<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\MarketCartItem;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Services\CartService;

class CartController extends Controller
{
    public function __construct(private readonly CartService $carts)
    {
    }

    public function show(Request $request)
    {
        $cart = $this->carts->current($request->user(), $request->session());
        $summary = $this->carts->summary($cart);

        return view('marketplace::store.cart', ['summary' => $summary, 'cart' => $cart]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('marketplace.max_quantity_per_item', 20)],
            'variant' => ['nullable', 'array', 'max:5'],
            'variant.*' => ['nullable', 'string', 'max:60'],
        ]);

        $product = MarketProduct::query()->visible()->with('vendor')->findOrFail($data['product_id']);
        $cart = $this->carts->current($request->user(), $request->session(), create: true);
        $this->carts->add($cart, $product, (int) ($data['quantity'] ?? 1), array_values($data['variant'] ?? []), $request->user());

        if ($request->input('buy_now')) {
            return redirect()->route('market.checkout');
        }

        return back()->with('success', 'تمت إضافة «'.$product->name.'» إلى سلة المشتريات.');
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.(int) config('marketplace.max_quantity_per_item', 20)],
        ]);

        $line = $this->ownedItem($request, $item);
        $this->carts->update($line, (int) $data['quantity']);

        return redirect()->route('market.cart.show')->with('success', 'تم تحديث الكمية.');
    }

    public function destroy(Request $request, int $item)
    {
        $line = $this->ownedItem($request, $item);
        $line->delete();

        return redirect()->route('market.cart.show')->with('success', 'تمت إزالة المنتج من السلة.');
    }

    private function ownedItem(Request $request, int $itemId): MarketCartItem
    {
        $cart = $this->carts->current($request->user(), $request->session());
        abort_if($cart === null, 404);

        return MarketCartItem::query()->where('cart_id', $cart->getKey())->with('product')->findOrFail($itemId);
    }
}
