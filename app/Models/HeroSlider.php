<?php

// Moved to \Modules\Content\Models\HeroSlider (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Models\HeroSlider') working.
// Do not add code here.

namespace App\Models;

if (! class_exists(HeroSlider::class, false)) {
    class_alias(\Modules\Content\Models\HeroSlider::class, HeroSlider::class);
}
