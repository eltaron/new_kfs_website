<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ComposerServiceProvider::class,
    App\Providers\SecurityServiceProvider::class,
    App\Providers\PaymentServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\EstidamaPanelProvider::class,
    App\Providers\Filament\GisPanelProvider::class,
    Modules\Syndicate\SyndicateServiceProvider::class,
    Modules\Marketplace\MarketplaceServiceProvider::class,
    Modules\SiteValidity\SiteValidityServiceProvider::class,
    // Modular monolith bounded contexts (docs/architecture.md). Keep after PaymentServiceProvider.
    Modules\Identity\IdentityServiceProvider::class,
    Modules\Content\ContentServiceProvider::class,
    Modules\Gis\GisServiceProvider::class,
    Modules\RemovalGovernance\RemovalGovernanceServiceProvider::class,
    Modules\Estidama\EstidamaServiceProvider::class,
    Modules\Tourism\TourismServiceProvider::class,
    Modules\Investment\InvestmentServiceProvider::class,
    Modules\Citizen\CitizenServiceProvider::class,
    Modules\Payments\PaymentsServiceProvider::class,
];
