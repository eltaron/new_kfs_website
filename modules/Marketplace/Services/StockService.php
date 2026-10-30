<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketProduct;

/**
 * Atomic stock handling with conditional UPDATEs (no read-modify-write races).
 *
 *   available = stock - reserved_stock
 *   reserve():  checkout of an e-Finance order holds the quantity (reserved_stock += q)
 *   commit():   payment confirmed / COD placed — stock -= q (and the hold is dropped)
 *   release():  payment failed / reservation expired / order cancelled — hold dropped
 *
 * Callers are expected to run these inside a DB transaction.
 */
class StockService
{
    /**
     * Hold stock for every line of the order. All-or-nothing: throws on the
     * first line that cannot be satisfied (the caller's transaction rolls back).
     *
     * @throws InsufficientStockException
     */
    public function reserve(MarketOrder $order): void
    {
        if ($order->stock_reserved || $order->stock_committed) {
            return;
        }

        foreach ($this->quantities($order) as $productId => $qty) {
            $affected = DB::table('market_products')
                ->where('id', $productId)
                ->whereNull('deleted_at')
                ->where('status', MarketProduct::STATUS_PUBLISHED)
                ->whereRaw('stock >= reserved_stock + ?', [$qty])
                ->update([
                    'reserved_stock' => DB::raw('reserved_stock + '.(int) $qty),
                    'updated_at' => now(),
                ]);

            if ($affected !== 1) {
                throw new InsufficientStockException($this->productName($order, $productId));
            }
        }

        $order->stock_reserved = true;
        $order->reserved_until = now()->addMinutes(max(1, (int) config('marketplace.reservation_minutes', 60)));
    }

    /**
     * Turn the order's quantities into a real stock decrement.
     *
     * strict=true  (COD checkout): throws if any line is short — nothing is sold.
     * strict=false (payment callback): money is already taken, so decrement what
     *              can be decremented and return false if any line fell short.
     *
     * @throws InsufficientStockException
     */
    public function commit(MarketOrder $order, bool $strict = true): bool
    {
        if ($order->stock_committed) {
            return true;
        }

        $complete = true;
        foreach ($this->quantities($order) as $productId => $qty) {
            $qty = (int) $qty;
            $query = DB::table('market_products')->where('id', $productId);

            if ($order->stock_reserved) {
                $affected = $query->where('stock', '>=', $qty)->update([
                    'stock' => DB::raw('stock - '.$qty),
                    'reserved_stock' => DB::raw("CASE WHEN reserved_stock >= {$qty} THEN reserved_stock - {$qty} ELSE 0 END"),
                    'sales_count' => DB::raw('sales_count + '.$qty),
                    'updated_at' => now(),
                ]);
            } else {
                $affected = $query->whereRaw('stock >= reserved_stock + ?', [$qty])->update([
                    'stock' => DB::raw('stock - '.$qty),
                    'sales_count' => DB::raw('sales_count + '.$qty),
                    'updated_at' => now(),
                ]);
            }

            if ($affected !== 1) {
                if ($strict) {
                    throw new InsufficientStockException($this->productName($order, $productId));
                }
                $complete = false;
                if ($order->stock_reserved) {
                    // Still drop our own hold so it does not leak.
                    DB::table('market_products')->where('id', $productId)->update([
                        'reserved_stock' => DB::raw("CASE WHEN reserved_stock >= {$qty} THEN reserved_stock - {$qty} ELSE 0 END"),
                    ]);
                }
            }
        }

        $order->stock_reserved = false;
        $order->stock_committed = true;

        return $complete;
    }

    /** Drop the order's hold (idempotent). */
    public function release(MarketOrder $order): void
    {
        if (! $order->stock_reserved) {
            return;
        }

        foreach ($this->quantities($order) as $productId => $qty) {
            $qty = (int) $qty;
            DB::table('market_products')->where('id', $productId)->update([
                'reserved_stock' => DB::raw("CASE WHEN reserved_stock >= {$qty} THEN reserved_stock - {$qty} ELSE 0 END"),
                'updated_at' => now(),
            ]);
        }

        $order->stock_reserved = false;
    }

    /** Put committed stock back (order cancelled after it was committed). */
    public function restock(MarketOrder $order): void
    {
        if (! $order->stock_committed) {
            $this->release($order);

            return;
        }

        foreach ($this->quantities($order) as $productId => $qty) {
            $qty = (int) $qty;
            DB::table('market_products')->where('id', $productId)->update([
                'stock' => DB::raw('stock + '.$qty),
                'sales_count' => DB::raw("CASE WHEN sales_count >= {$qty} THEN sales_count - {$qty} ELSE 0 END"),
                'updated_at' => now(),
            ]);
        }

        $order->stock_committed = false;
    }

    /**
     * Quantity per product, sorted by id so concurrent orders lock rows in the
     * same order (avoids deadlocks).
     *
     * @return array<int, int>
     */
    private function quantities(MarketOrder $order): array
    {
        $order->loadMissing('items');

        $map = [];
        foreach ($order->items as $item) {
            if ($item->product_id === null || $item->fulfillment_status === 'cancelled') {
                continue;
            }
            $map[$item->product_id] = ($map[$item->product_id] ?? 0) + (int) $item->quantity;
        }
        ksort($map);

        return $map;
    }

    private function productName(MarketOrder $order, int $productId): string
    {
        return (string) ($order->items->firstWhere('product_id', $productId)?->product_name ?? 'أحد المنتجات');
    }
}
