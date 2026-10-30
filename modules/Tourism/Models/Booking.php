<?php

namespace Modules\Tourism\Models;

use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model implements Payable
{
    use HasFactory, HasPayments;

    protected $fillable = [
        'landmark_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'number_of_people',
        'visit_date',
        'visit_time',
        'special_requests',
        'status',
        'user_id',
        'payment_status',
        'paid_at',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'total_price' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function landmark(): BelongsTo
    {
        return $this->belongsTo(Landmark::class);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'confirmed' => 'مؤكد',
            'pending' => 'قيد الانتظار',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => 'غير معروف',
        };
    }
    // ── Payable (landmark visit booking; guests pay through a signed link) ──

    public function payableAmount(): string
    {
        return Money::format($this->total_price);
    }

    public function payableDescription(): string
    {
        return 'حجز زيارة: ' . ($this->landmark?->name ?? 'معلم سياحي') . ' — حجز #' . $this->id;
    }

    public function payableOwner(): ?User
    {
        return $this->user_id ? User::find($this->user_id) : null;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBePaid(): bool
    {
        return ! $this->isPaid()
            && in_array($this->status, ['pending', 'confirmed'], true)
            && Money::isPositive($this->total_price);
    }

    public function markPaid(Transaction $transaction): void
    {
        $this->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
    }

    public function markPaymentFailed(Transaction $transaction): void
    {
        // stays unpaid; the customer can retry with the same link
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return $this->checkoutUrl();
    }
}
