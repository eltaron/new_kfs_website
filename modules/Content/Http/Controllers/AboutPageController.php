<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\OfficialRole;
use Modules\Content\Models\Setting;

class AboutPageController extends Controller
{
    public function governorMessage()
    {
        // Message text/image come from Settings (global $settings); the governor's
        // name/photo come from the current «governor» role so they follow admin changes.
        $governor = OfficialRole::where('role_name', 'governor')
            ->where('is_current', true)
            ->with('official')
            ->first();

        return view('about.governor', ['governor' => $governor]);
    }
}
