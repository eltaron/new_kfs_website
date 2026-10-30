<?php

namespace App\Providers\Filament;

use BladeUI\Icons\Factory;
use Filament\Support\Contracts\IconProvider;

class FontAwesomeIconProvider implements IconProvider
{
    public function __construct(protected Factory $factory) {}

    public function get(string $name): ?string
    {
        // We still assume 'fas' as the default set if none is provided.
        // blade-fontawesome expects the full name like 'fas-bed' for the component.
        if (! str_contains($name, '-')) {
            $name = "fas-{$name}";
        }

        try {
            // Here, we use the correct syntax for Blade UI Kit to get the component HTML
            return blade(
                "components.icon", // the blade <x-icon component
                ['name' => $name]      // passing the name attribute
            )->render();
        } catch (\Exception $e) {
            // If the icon is not found, return null
            return null;
        }
    }
}
