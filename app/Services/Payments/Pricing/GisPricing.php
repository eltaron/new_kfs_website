<?php

namespace App\Services\Payments\Pricing;

use Modules\Gis\Models\GisSubService;
use App\Services\Payments\Money;

/**
 * Server-side price of a GIS sub-service request, mirroring the estimate in
 * gis/apply/start.blade.php and the settings of GisSubServiceResource:
 *
 *   fixed   : base_price
 *   formula : area × multiplier + base_price (+ fixed_extra, + static points)
 *   tiered  : tier price by area (+ overflow per unit above threshold)
 *             (+ extra points above point_threshold)
 *   + VAT 14% on the core price, + martyr stamp + SMS fees.
 *
 * Inputs are validated as numeric >= 0 before they reach this class; values
 * are still clamped here so a negative total is impossible.
 */
class GisPricing
{
    public const VAT_RATE = 0.14;

    /** Numeric inputs that influence the price and must be validated numeric|min:0. */
    public function numericInputs(GisSubService $service): array
    {
        return array_values(array_unique(array_filter([
            ...array_values($this->inputFields($service)),
            'area_m2',
            'points_count',
        ])));
    }

    /**
     * Which dynamic form field feeds each pricing input. Admins name fields freely
     * ("area" / "area_m2", "points" / "points_count"), so the explicit
     * pricing_settings.variable wins, then the first known name present in the form.
     *
     * @return array{area:string, points:string, static_points:string}
     */
    public function inputFields(GisSubService $service): array
    {
        $settings = $service->pricing_settings ?? [];
        $names = array_column(array_filter((array) ($service->dynamic_fields ?? []), 'is_array'), 'name');
        $pick = fn (array $candidates) => current(array_intersect($candidates, $names)) ?: $candidates[0];

        return [
            'area' => ! empty($settings['variable']) && in_array($settings['variable'], $names, true)
                ? $settings['variable']
                : $pick(['area_m2', 'area']),
            'points' => $pick(['points_count', 'points']),
            'static_points' => $pick(['points_static_count', 'static_points']),
        ];
    }

    /**
     * @return array{core:string, vat:string, martyr_stamp_fee:string, sms_fee:string, total:string}
     */
    public function quote(GisSubService $service, array $formData): array
    {
        $settings = $service->pricing_settings ?? [];
        $type = $service->pricing_type ?: 'fixed';
        $base = max(0.0, (float) $service->base_price);
        $fields = $this->inputFields($service);
        $area = $this->num($formData[$fields['area']] ?? 0);

        $core = $base;

        if ($type === 'formula') {
            $core = $area * $this->num($settings['multiplier'] ?? 0) + $base + $this->num($settings['fixed_extra'] ?? 0);

            if (isset($settings['static_point_price'])) {
                $core += $this->num($formData[$fields['static_points']] ?? 0) * $this->num($settings['static_point_price']);
            }
        } elseif ($type === 'tiered') {
            $tiers = array_values(array_filter((array) ($settings['tiers'] ?? []), fn ($t) => is_array($t) && isset($t['max'], $t['price'])));
            usort($tiers, fn ($a, $b) => (float) $a['max'] <=> (float) $b['max']);

            if ($tiers !== []) {
                $core = $this->num(end($tiers)['price']);
                foreach ($tiers as $tier) {
                    if ($area <= (float) $tier['max']) {
                        $core = $this->num($tier['price']);
                        break;
                    }
                }
            }

            $threshold = $this->num($settings['overflow_threshold'] ?? 0);
            if (! empty($settings['has_overflow']) && $area > $threshold) {
                $unit = $this->num($settings['overflow_unit_size'] ?? 4200) ?: 4200;
                $core += ceil(($area - $threshold) / $unit) * $this->num($settings['overflow_price'] ?? 0);
            }

            $pointThreshold = $this->num($settings['point_threshold'] ?? 0);
            $points = $this->num($formData[$fields['points']] ?? 0);
            if (! empty($settings['point_threshold']) && $points > $pointThreshold) {
                $core += ($points - $pointThreshold) * $this->num($settings['point_extra'] ?? 0);
            }
        }

        $vat = $service->has_vat ? $core * self::VAT_RATE : 0.0;
        $martyr = $this->num($service->martyr_stamp_fee ?? 0);
        $sms = $this->num($service->sms_fee ?? 0);

        return [
            'core' => Money::format($core),
            'vat' => Money::format($vat),
            'martyr_stamp_fee' => Money::format($martyr),
            'sms_fee' => Money::format($sms),
            'total' => Money::format($core + $vat + $martyr + $sms),
        ];
    }

    private function num(mixed $value): float
    {
        return is_numeric($value) ? max(0.0, (float) $value) : 0.0;
    }
}
