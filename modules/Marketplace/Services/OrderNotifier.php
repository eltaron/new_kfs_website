<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Models\MarketOrder;
use Modules\Marketplace\Models\MarketOrderItem;
use Modules\Marketplace\Models\MarketProduct;
use Modules\Marketplace\Models\MarketVendor;
use Modules\Marketplace\Notifications\MarketplaceNotification;

/**
 * Sends marketplace notifications after the surrounding DB transaction commits.
 * A failing mail transport never breaks checkout or the payment callback.
 */
class OrderNotifier
{
    /** Order confirmed (paid via e-Finance, or placed as cash-on-delivery). */
    public function orderConfirmed(MarketOrder $order): void
    {
        $orderId = $order->getKey();

        $this->afterCommit(function () use ($orderId) {
            $order = MarketOrder::with(['user', 'items.vendor.user'])->find($orderId);
            if (! $order) {
                return;
            }

            $paidText = $order->payment_method === MarketOrder::METHOD_COD ? 'وسيتم الدفع عند الاستلام' : 'وتم استلام المبلغ بنجاح';
            $this->send($order->user, new MarketplaceNotification(
                kind: 'order_confirmed',
                title: 'تم تأكيد طلبك رقم '.$order->number,
                body: "تم تأكيد طلبك {$paidText}. سنُعلمك عند شحن المنتجات.",
                url: route('market.orders.show', $order),
                actionLabel: 'متابعة الطلب',
                color: 'success',
                icon: 'heroicon-o-check-circle',
                context: ['order' => $order->number],
            ));

            foreach ($order->items->groupBy('vendor_id') as $items) {
                /** @var MarketOrderItem $first */
                $first = $items->first();
                $count = $items->sum('quantity');
                $this->send($first->vendor?->user, new MarketplaceNotification(
                    kind: 'new_order',
                    title: 'طلب جديد رقم '.$order->number,
                    body: "لديك طلب جديد يحتوي على {$count} قطعة. برجاء تجهيزه وتحديث حالته.",
                    url: $this->panelUrl('filament.vendor.resources.orders.index'),
                    actionLabel: 'عرض الطلبات',
                    color: 'success',
                    icon: 'heroicon-o-shopping-cart',
                    context: ['order' => $order->number, 'vendor_id' => $first->vendor_id],
                ));
            }
        });
    }

    public function fulfillmentChanged(MarketOrderItem $item): void
    {
        $itemId = $item->getKey();

        $this->afterCommit(function () use ($itemId) {
            $item = MarketOrderItem::with(['order.user', 'vendor'])->find($itemId);
            if (! $item || ! $item->order) {
                return;
            }
            $note = $item->tracking_note ? ' — '.$item->tracking_note : '';
            $this->send($item->order->user, new MarketplaceNotification(
                kind: 'order_status',
                title: 'تحديث على طلبك رقم '.$item->order->number,
                body: "حالة المنتج «{$item->product_name}» أصبحت: {$item->fulfillmentLabel()}{$note}",
                url: route('market.orders.show', $item->order),
                actionLabel: 'متابعة الطلب',
                color: $item->fulfillment_status === MarketOrderItem::FULFILLMENT_CANCELLED ? 'danger' : 'info',
                icon: 'heroicon-o-truck',
                context: ['order' => $item->order->number, 'status' => $item->fulfillment_status],
            ));
        });
    }

    public function vendorApplicationSubmitted(MarketVendor $vendor): void
    {
        $vendorId = $vendor->getKey();

        $this->afterCommit(function () use ($vendorId) {
            $vendor = MarketVendor::find($vendorId);
            if (! $vendor) {
                return;
            }
            $roles = $this->existingRoles((array) config('marketplace.notify_roles', []));
            $admins = $roles ? User::role($roles)->get() : collect();
            foreach ($admins as $admin) {
                $this->send($admin, new MarketplaceNotification(
                    kind: 'vendor_application',
                    title: 'طلب انضمام بائع جديد',
                    body: "قدّم «{$vendor->store_name}» طلب انضمام إلى متجر ذوي الهمم ويحتاج إلى مراجعة.",
                    url: $this->panelUrl('filament.store-admin.resources.vendors.index'),
                    actionLabel: 'مراجعة الطلبات',
                    color: 'warning',
                    icon: 'heroicon-o-user-plus',
                    context: ['vendor_id' => $vendor->getKey()],
                ));
            }
        });
    }

    public function vendorStatusChanged(MarketVendor $vendor): void
    {
        $vendorId = $vendor->getKey();

        $this->afterCommit(function () use ($vendorId) {
            $vendor = MarketVendor::with('user')->find($vendorId);
            if (! $vendor) {
                return;
            }
            [$title, $body, $color, $url] = match ($vendor->status) {
                MarketVendor::STATUS_APPROVED => ['تم اعتماد متجرك', "مبروك! تم اعتماد متجر «{$vendor->store_name}». يمكنك الآن إضافة منتجاتك من لوحة البائع.", 'success', $this->panelUrl('filament.vendor.pages.dashboard')],
                MarketVendor::STATUS_REJECTED => ['لم يتم قبول طلب المتجر', 'نأسف، لم يتم قبول طلبك.'.($vendor->review_notes ? ' السبب: '.$vendor->review_notes : ''), 'danger', route('market.sell.status')],
                MarketVendor::STATUS_SUSPENDED => ['تم إيقاف متجرك مؤقتاً', 'تم إيقاف متجرك مؤقتاً.'.($vendor->review_notes ? ' السبب: '.$vendor->review_notes : ''), 'danger', route('market.sell.status')],
                default => ['طلبك قيد المراجعة', 'طلب متجرك قيد المراجعة حالياً.', 'warning', route('market.sell.status')],
            };
            $this->send($vendor->user, new MarketplaceNotification(
                kind: 'vendor_status',
                title: $title,
                body: $body,
                url: $url,
                color: $color,
                icon: 'heroicon-o-building-storefront',
                context: ['vendor_id' => $vendor->getKey(), 'status' => $vendor->status],
            ));
        });
    }

    /** A vendor submitted a product for review: tell the store moderators. */
    public function productSubmitted(MarketProduct $product): void
    {
        $productId = $product->getKey();

        $this->afterCommit(function () use ($productId) {
            $product = MarketProduct::with('vendor')->find($productId);
            if (! $product) {
                return;
            }
            $roles = $this->existingRoles((array) config('marketplace.notify_roles', []));
            $admins = $roles ? User::role($roles)->get() : collect();
            foreach ($admins as $admin) {
                $this->send($admin, new MarketplaceNotification(
                    kind: 'product_submitted',
                    title: 'منتج جديد بانتظار المراجعة',
                    body: "أرسل متجر «{$product->vendor?->store_name}» المنتج «{$product->name}» للمراجعة قبل النشر.",
                    url: $this->panelUrl('filament.store-admin.resources.products.index'),
                    actionLabel: 'مراجعة المنتجات',
                    color: 'warning',
                    icon: 'heroicon-o-cube',
                    context: ['product_id' => $product->getKey()],
                ));
            }
        });
    }

    public function productModerated(MarketProduct $product): void
    {
        $productId = $product->getKey();

        $this->afterCommit(function () use ($productId) {
            $product = MarketProduct::with('vendor.user')->find($productId);
            if (! $product) {
                return;
            }
            $approved = $product->status === MarketProduct::STATUS_PUBLISHED;
            $this->send($product->vendor?->user, new MarketplaceNotification(
                kind: 'product_moderated',
                title: $approved ? 'تم نشر منتجك' : 'منتجك يحتاج إلى تعديل',
                body: $approved
                    ? "تم نشر «{$product->name}» في المتجر وأصبح متاحاً للشراء."
                    : "لم يتم نشر «{$product->name}».".($product->moderation_note ? ' الملاحظات: '.$product->moderation_note : ''),
                url: $this->panelUrl('filament.vendor.resources.products.index'),
                color: $approved ? 'success' : 'warning',
                icon: 'heroicon-o-cube',
                context: ['product_id' => $product->getKey(), 'status' => $product->status],
            ));
        });
    }

    private function send(?User $user, MarketplaceNotification $notification): void
    {
        if (! $user) {
            return;
        }
        try {
            Notification::send($user, $notification);
        } catch (\Throwable $e) {
            Log::warning('Marketplace notification failed', ['kind' => $notification->kind, 'error' => $e->getMessage()]);
        }
    }

    private function afterCommit(\Closure $callback): void
    {
        DB::afterCommit($callback);
    }

    private function panelUrl(string $route): ?string
    {
        return Route::has($route) ? route($route) : null;
    }

    /** spatie's role() scope throws for unknown role names. */
    private function existingRoles(array $roles): array
    {
        $roleClass = app(\Spatie\Permission\PermissionRegistrar::class)->getRoleClass();

        return $roleClass::query()->whereIn('name', $roles)->where('guard_name', 'web')->pluck('name')->all();
    }
}
