<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Modular-monolith boundary guard (docs/architecture.md, "Allowed dependencies").
 *
 * Code inside modules/<A> must not use another module's Eloquent models
 * (Modules\<B>\Models\*) nor legacy App\Models\* aliases — only the Identity kernel
 * (App\Models\User|Role|Permission), the Payments contract (App\Contracts, App\Concerns),
 * domain events and modules/<B>/Contracts.
 *
 * References that existed when the monolith was split are frozen in ALLOWED below:
 * they are the backlog of contracts to write. Remove entries as they are fixed;
 * never add new ones without updating docs/architecture.md.
 */
class ModuleBoundariesTest extends TestCase
{
    /** Identity shared kernel models every module may use. */
    private const KERNEL_MODELS = ['User', 'Role', 'Permission'];

    /** "<FromModule> -> <Target>" frozen at split time (Target = "<Module>\Model" or "App\Model"). */
    private const ALLOWED = [
        // @generated-allow-list-start (frozen 2026-10-07 at the modular split)
        // Payables reading their own transactions/fees (to become Payments contract queries):
        'Citizen -> Payments\FulfillmentFee',
        'Citizen -> Payments\Transaction',
        'Estidama -> Payments\Transaction',
        'Gis -> Payments\FulfillmentFee',
        'Gis -> Payments\Transaction',
        'Marketplace -> Payments\Transaction',
        'RemovalGovernance -> Payments\Transaction',
        'Tourism -> Payments\Transaction',
        'Payments -> Citizen\ServiceSubmission',   // FulfillmentFee::for*() helpers
        'Payments -> Gis\GisSubmission',
        // Citizen dashboard / lookup aggregates (to become read-model contracts):
        'Citizen -> App\SyndicateCertificate',
        'Citizen -> Estidama\Enrollment',
        'Citizen -> Estidama\TrainingApplication',
        'Citizen -> Gis\GisSubmission',
        'Citizen -> Syndicate\Project',
        'Citizen -> Syndicate\SyndicateRequest',
        // Shared reference geography (centers / city villages) and polymorphic images:
        'Citizen -> Content\Center',
        'Citizen -> Tourism\CityVillage',
        'Content -> Tourism\CityVillage',
        'Tourism -> Content\Center',
        'Tourism -> Content\Image',
        'Investment -> Content\Image',
        'Estidama -> Content\Setting',
        // Home page / search / interactive map aggregations (to become per-module query contracts):
        'Content -> Citizen\Service',
        'Content -> Investment\Investment',
        'Content -> Investment\InvestmentPlan',
        'Content -> Investment\Project',
        'Content -> Tourism\CityGuideCategory',
        'Content -> Tourism\Landmark',
        'Content -> Tourism\Location',
        'Gis -> Investment\Project',
        'Gis -> Tourism\Landmark',
        'Gis -> Tourism\Location',
        // Syndicate test referencing the legacy certificate model:
        'Syndicate -> App\SyndicateCertificate',
        // @generated-allow-list-end
    ];

    public function test_modules_only_reference_other_modules_through_contracts(): void
    {
        $violations = [];
        foreach ($this->references() as $edge => $files) {
            if (! in_array($edge, self::ALLOWED, true)) {
                $violations[] = $edge.'  (in '.implode(', ', array_slice($files, 0, 3)).')';
            }
        }

        $this->assertSame([], $violations, "New cross-module model dependencies found.\n"
            ."Use the target module's Contracts/ interface or a domain event instead (docs/architecture.md):\n"
            .implode("\n", $violations));
    }

    public function test_allow_list_has_no_stale_entries(): void
    {
        $stale = array_values(array_diff(self::ALLOWED, array_keys($this->references())));

        $this->assertSame([], $stale, "These allow-listed dependencies no longer exist — remove them from ALLOWED:\n".implode("\n", $stale));
    }

    /** @return array<string, list<string>> edge => files */
    private function references(): array
    {
        $root = dirname(__DIR__, 2);
        $edges = [];

        foreach (glob($root.'/modules/*', GLOB_ONLYDIR) as $moduleDir) {
            $module = basename($moduleDir);
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $code = file_get_contents($file->getPathname());
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

                preg_match_all('/Modules\\\\(\w+)\\\\Models\\\\(\w+)/', $code, $m, PREG_SET_ORDER);
                foreach ($m as [, $target, $model]) {
                    if ($target !== $module) {
                        $edges["$module -> $target\\$model"][$rel] = true;
                    }
                }

                preg_match_all('/App\\\\Models\\\\(\w+)/', $code, $m, PREG_SET_ORDER);
                foreach ($m as [, $model]) {
                    if (! in_array($model, self::KERNEL_MODELS, true)) {
                        $edges["$module -> App\\$model"][$rel] = true;
                    }
                }
            }
        }

        ksort($edges);

        return array_map(fn (array $files) => array_keys($files), $edges);
    }
}
