<?php

namespace App\Services\Payments\Pricing;

use Modules\Citizen\Models\Service;
use App\Services\Payments\Money;

/**
 * Server-side price of a generic e-service submission. The client-side
 * estimate in services/show.blade.php is display only; this is what is charged.
 *
 * Semantics follow the admin form (ServiceResource):
 *   base_price               = price per m² (fixed pricing)
 *   category_pricing[].price_multiplier = price per m² in that area/category
 *   insurance_percentage     = % of the subtotal (+ flat insurance_fee if any)
 *   VAT 14% on the subtotal, then martyr stamp + SMS fees.
 *
 * Note: Service::calculateTotal() predates the admin form (it treats
 * price_multiplier as a multiplier and ignores insurance_percentage), so it is
 * not used for charging.
 */
class ServicePricing
{
    /** Form-field names that carry the quantity (m²) the price is multiplied by. */
    public const QUANTITY_FIELDS = ['area', 'area_m2', 'number', 'quantity'];

    public const VAT_RATE = 0.14;

    /**
     * @param  array<string,mixed>  $formData  validated input
     * @return array{price_per_unit:string, quantity:string, subtotal:string, insurance:string, vat:string, martyr_stamp_fee:string, sms_fee:string, total:string}
     */
    public function quote(Service $service, array $formData): array
    {
        $quantity = $this->quantity($service, $formData);
        $pricePerUnit = $this->pricePerUnit($service, $formData['category'] ?? null);

        $subtotal = $quantity * $pricePerUnit;
        $insurance = $subtotal * ((float) ($service->insurance_percentage ?? 0) / 100) + (float) ($service->insurance_fee ?? 0);
        $vat = $service->has_vat ? $subtotal * self::VAT_RATE : 0.0;
        $martyr = (float) ($service->martyr_stamp_fee ?? 0);
        $sms = (float) ($service->sms_fee ?? 0);

        return [
            'price_per_unit' => Money::format($pricePerUnit),
            'quantity' => number_format($quantity, 2, '.', ''),
            'subtotal' => Money::format($subtotal),
            'insurance' => Money::format($insurance),
            'vat' => Money::format($vat),
            'martyr_stamp_fee' => Money::format($martyr),
            'sms_fee' => Money::format($sms),
            'total' => Money::format($subtotal + $insurance + $vat + $martyr + $sms),
        ];
    }

    public function quantityField(Service $service): ?string
    {
        $names = array_column($service->form_fields ?? [], 'name');
        foreach (self::QUANTITY_FIELDS as $candidate) {
            if (in_array($candidate, $names, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function quantity(Service $service, array $formData): float
    {
        $field = $this->quantityField($service);
        if ($field === null) {
            return 1.0; // flat-priced service
        }

        return max(0.0, (float) ($formData[$field] ?? 0));
    }

    private function pricePerUnit(Service $service, mixed $category): float
    {
        if ($service->pricing_type === 'category' && is_array($service->category_pricing)) {
            foreach ($service->category_pricing as $row) {
                if (($row['name'] ?? null) === $category) {
                    return max(0.0, (float) ($row['price_multiplier'] ?? 0));
                }
            }
        }

        return max(0.0, (float) $service->base_price);
    }
}
