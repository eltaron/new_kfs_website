<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\OfficialRole;

class OfficialsController extends Controller
{
    // A single method to handle all four pages
    public function show($role)
    {
        // الربط بين السلج في الرابط والمسمى في الداتابيز والعنوان العربي
        $roleMap = [
            'governor' => ['db' => 'governor', 'title' => 'المحافظ', 'description' => 'محافظ'],
            'deputy-governor' => ['db' => 'deputy_governor', 'title' => 'نائب المحافظ', 'description' => 'نائب محافظ'],
            'secretary-general' => ['db' => 'secretary_general', 'title' => 'السكرتير العام', 'description' => 'سكرتير عام'],
            'assistant-secretary-general' => ['db' => 'assistant_secretary_general', 'title' => 'السكرتير العام المساعد', 'description' => 'سكرتير عام مساعد'],
        ];

        if (!array_key_exists($role, $roleMap)) {
            abort(404);
        }

        $target = $roleMap[$role];

        // جلب المسؤول الحالي
        $currentOfficial = OfficialRole::where('role_name', $target['db'])
            ->where('is_current', true)
            ->with('official')->first();

        // جلب المسؤولين السابقين
        $previousOfficials = OfficialRole::where('role_name', $target['db'])
            ->where('is_current', false)
            ->with('official')
            ->orderBy('start_year', 'desc')
            ->get();

        return view('officials.show', [
            'pageTitle' => $target['title'],
            'description' => $target['description'],
            'currentOfficial' => $currentOfficial,
            'previousOfficials' => $previousOfficials,
        ]);
    }
}
