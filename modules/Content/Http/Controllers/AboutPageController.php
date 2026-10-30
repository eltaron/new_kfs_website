<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\Setting;

class AboutPageController extends Controller
{
    public function governorMessage()
    {
        // Settings are loaded globally via ComposerServiceProvider
        return view('about.governor');
    }
}
