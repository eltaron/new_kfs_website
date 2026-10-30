<?php

namespace Modules\Marketplace\Models;

use App\Concerns\HasPayments;
use App\Contracts\Payable;
use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Marketplace\Services\OrderNotifier;
use Modules\Marketplace\Services\StockService;

/**
 * A buyer's order. One order can contain items from several vendors; each
 * vendor's items form a "sub-order" (order_items grouped by vendor_id).
 *
 * Paid through the unified e-Finance service (morph alias "market_order").
 *
 * @property int $id
 * @property string $number
 * @property string $user_id
 * @property string $status
 * @property string $payment_method
 * @property string $total
 */
class MarketOrder extends Model implements Payable
{
    use HasPayments {
        isPaid as protected hasCompletedTransaction;
    }

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_PAID = 'paid';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT => 'بانتظار الدفع',
        self::STATUS_PAYMENT_FAILED => 'تعذر الدفع',
        self::STATUS_PAID => 'تم الدفع',
        self::STATUS_PROCESSING => 'قيد التجهيز',
        self::STATUS_SHIPPED => 'تم الشحن',
        self::STATUS_DELIVERED => 'تم التسليم',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_CANCELLED => 'ملغي',
    ];

    public const METHOD_EFINANCE = 'efinance';
    public const METHOD_COD = 'cod';

    public const METHODS = [
        self::METHOD_EFINANCE => 'دفع إلكتروني (e-Finance)',
        self::METHOD_COD => 'الدفع عند الاستلام',
    ];

    /** Statuses in which vendors should prepare / ship the items. */
    public const FULFILLABLE = [self::STATUS_PAID, self::STATUS_PROCESSING, self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_COMPLETED];

    protected $table = 'market_orders';

    protected $fillable = [
        'user_id', 'status', 'payment_method', 'subtotal', 'shipping_total', 'commission_total', 'total',
        'shipping_name', 'shipping_phone', 'shipping_center', 'shipping_address', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'total' => 'decimal:2',
        'stock_reserved' => 'boolean',
        'stock_committed' => 'boolean',
        'needs_review' => 'boolean',
        'reserved_until' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketOrder $order) {
            if (blank($order->number)) {
                do {
                    $number = 'MK'.now()->format('ymd').'-'.strtoupper(Str::random(6));
                } while (static::where('number', $number)->exists());
                $order->number = $number;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketOrderItem::class, 'order_id')->orderBy('vendor_id')->orderBy('id');
    }

    /** @return Collection<int, Collection<int, MarketOrderItem>> items grouped per vendor (sub-orders) */
    public function subOrders(): Collection
    {
        return $this->items->groupBy('vendor_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID, self::STATUS_DELIVERED, self::STATUS_COMPLETED => 'status-success',
            self::STATUS_PENDING_PAYMENT, self::STATUS_PROCESSING => 'status-pending',
            self::STATUS_SHIPPED => 'status-info',
            default => 'status-danger',
        };
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->payment_method === self::METHOD_EFINANCE
            && in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_FAILED], true);
    }

    public function isFulfillable(): bool
    {
        return in_array($this->status, self::FULFILLABLE, true);
    }

    // ─────────────────────────────────────────────────────────────────
    // Payable
    // ─────────────────────────────────────────────────────────────────

    public function payableAmount(): string
    {
        return Money::format($this->total);
    }

    public function payableDescription(): string
    {
        return 'طلب شراء من متجر ذوي الهمم رقم '.$this->number;
    }

    public function payableOwner(): ?User
    {
        return $this->user;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null || $this->hasCompletedTransaction();
    }

    /** Only an e-Finance order whose stock is still held may start a payment. */
    public function canBePaid(): bool
    {
        return $this->payment_method === self::METHOD_EFINANCE
            && $this->status === self::STATUS_PENDING_PAYMENT
            && $this->stock_reserved
            && ! $this->isPaid()
            && Money::isPositive($this->payableAmount());
    }

    /**
     * Gateway confirmed payment (called inside the callback DB transaction with
     * this row locked). Converts the reservation into a real stock decrement.
     */
    public function markPaid(Transaction $transaction): void
    {
        $this->loadMissing('items');

        $complete = app(StockService::class)->commit($this, strict: false);
        if (! $complete) {
            $this->needs_review = true;
            Log::critical('Marketplace order paid but stock was insufficient — manual review required', [
                'order' => $this->number, 'transaction' => $transaction->getKey(),
            ]);
        }

        if ($this->status === self::STATUS_CANCELLED) {
            // Paid after expiry/cancellation: keep the money trail, reopen for fulfilment, flag for admins.
            $this->needs_review = true;
            $this->cancelled_at = null;
        }

        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        $this->reserved_until = null;
        $this->save();

        app(OrderNotifier::class)->orderConfirmed($this);
    }

    /** A gateway attempt failed: release the held stock (never undoes a paid order). */
    public function markPaymentFailed(Transaction $transaction): void
    {
        if ($this->paid_at !== null || ! in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PAYMENT_FAILED], true)) {
            return;
        }

        $this->loadMissing('items');
        app(StockService::class)->release($this);

        $this->status = self::STATUS_PAYMENT_FAILED;
        $this->reserved_until = null;
        $this->save();
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return route('market.orders.show', $this);
    }
}
