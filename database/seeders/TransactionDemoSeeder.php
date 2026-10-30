<?php

namespace Database\Seeders;

use Modules\Tourism\Models\Booking;
use Modules\Payments\Models\FulfillmentFee;
use Modules\Gis\Models\GisSubmission;
use Modules\RemovalGovernance\Models\RemovalOrder;
use Modules\Citizen\Models\ServiceSubmission;
use Modules\Tourism\Models\TourBooking;
use Modules\Payments\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * معاملات الدفع المرتبطة بالعناصر المدفوعة (الخدمة الموحدة للدفع — morph aliases من PaymentServiceProvider):
 * paid → completed، pending → pending، failed → failed. المفتاح sender_request_number = DEMO-<alias>-<id>.
 */
class TransactionDemoSeeder extends Seeder
{
    private const PAYABLES = [
        'service_submission' => ServiceSubmission::class,
        'gis_submission' => GisSubmission::class,
        'fulfillment_fee' => FulfillmentFee::class,
        'landmark_booking' => Booking::class,
        'tour_booking' => TourBooking::class,
        'removal_order' => RemovalOrder::class,
    ];

    public function run(): void
    {
        $count = 0;
        foreach (self::PAYABLES as $alias => $class) {
            $statusColumn = $class === FulfillmentFee::class ? 'status' : 'payment_status';
            $class::query()->whereIn($statusColumn, ['paid', 'pending', 'failed'])->each(function (Model $item) use ($alias, $statusColumn, &$count) {
                $state = $item->{$statusColumn};
                $amount = $this->amount($item);
                if ($amount <= 0) {
                    return;
                }
                $txStatus = match ($state) {
                    'paid' => Transaction::STATUS_COMPLETED,
                    'failed' => Transaction::STATUS_FAILED,
                    default => Transaction::STATUS_PENDING,
                };
                $at = $item->paid_at ?? $item->created_at ?? now();
                $ref = 'DEMO-' . strtoupper(substr(md5($alias), 0, 4)) . '-' . substr(str_replace('-', '', (string) $item->getKey()), 0, 20);

                $tx = Transaction::firstOrNew(['sender_request_number' => $ref]);
                $tx->forceFill([
                    'user_id' => method_exists($item, 'payableOwner') ? $item->payableOwner()?->id : null,
                    'transactionable_type' => $alias,
                    'transactionable_id' => (string) $item->getKey(),
                    'amount' => $amount,
                    'description' => method_exists($item, 'payableDescription') ? mb_substr($item->payableDescription(), 0, 250) : $alias,
                    'status' => $txStatus,
                    'payment_gateway' => 'fake',
                    'payment_request_number' => $txStatus === Transaction::STATUS_PENDING ? null : 'PRN' . crc32($ref),
                    'authorization_code' => $txStatus === Transaction::STATUS_COMPLETED ? (string) (100000 + crc32($ref) % 900000) : null,
                    'gateway_amount' => $amount,
                    'collection_fees' => 0,
                    'failure_reason' => $txStatus === Transaction::STATUS_FAILED ? 'رفض البنك المصدر للبطاقة (تجريبي)' : null,
                    'expires_at' => $txStatus === Transaction::STATUS_PENDING ? now()->addDay() : null,
                    'metadata' => ['demo' => true],
                    'completed_at' => $txStatus === Transaction::STATUS_COMPLETED ? $at : null,
                    'failed_at' => $txStatus === Transaction::STATUS_FAILED ? $at : null,
                    'created_at' => $item->created_at ?? now(),
                ])->save();
                $count++;
            });
        }

        $this->command?->info("Seeded/updated {$count} demo transactions.");
    }

    private function amount(Model $item): float
    {
        if (method_exists($item, 'payableAmount')) {
            try {
                return (float) $item->payableAmount();
            } catch (\Throwable) {
                // fall through
            }
        }

        return (float) ($item->total_amount ?? $item->amount ?? $item->total_price ?? $item->total_cost ?? 0);
    }
}
