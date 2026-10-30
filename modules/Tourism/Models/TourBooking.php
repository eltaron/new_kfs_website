<?php

namespace Modules\Tourism\Models;

use Modules\Payments\Models\Transaction;
use App\Models\User;
use App\Concerns\HasPayments;
use App\Contracts\Payable;
use App\Services\Payments\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourBooking extends Model implements Payable
{
    use HasFactory, HasPayments;

    protected $fillable = [
        'company_name',
        'tour_name',
        'trip_date',
        'trip_types',
        'people_count',
        'meals_booking',
        'desouk_to_fouh_details',
        'edfina_to_desouk_details',
        'total_cost',
        'status',
        'user_id',
        'payment_status',
        'paid_at',
        'admin_notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'trip_types' => 'array',
        'total_cost' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function getTripTypesListAttribute(): string
    {
        return $this->trip_types ? implode(' - ', $this->trip_types) : '—';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'مؤكد',
            'pending' => 'قيد الانتظار',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => 'غير معروف',
        };
    }
    // ── Payable (tour company booking; total_cost is set by the admin) ──

    public function payableAmount(): string
    {
        return Money::format($this->total_cost);
    }

    public function payableDescription(): string
    {
        return 'حجز رحلة سياحية: ' . $this->tour_name . ' — ' . $this->company_name . ' — حجز #' . $this->id;
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
            && Money::isPositive($this->total_cost);
    }

    public function markPaid(Transaction $transaction): void
    {
        $this->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
    }

    public function markPaymentFailed(Transaction $transaction): void
    {
        // stays unpaid; the company can retry with the same link
    }

    public function paymentReturnUrl(Transaction $transaction): string
    {
        return $this->checkoutUrl();
    }

}
