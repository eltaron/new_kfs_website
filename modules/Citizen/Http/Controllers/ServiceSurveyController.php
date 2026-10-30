<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceSurveyRequest;
use Modules\Citizen\Models\ServiceSurvey;
use App\Models\User;
use App\Notifications\NewServiceSurvey;
use Illuminate\Support\Facades\Notification;

class ServiceSurveyController extends Controller
{
    public function create()
    {
        $centers = [
            'المركز التكنولوجي بمدينة كفر الشيخ',
            'المركز التكنولوجي بمدينة دسوق',
            'المركز التكنولوجي بمدينة بيلا',
            'المركز التكنولوجي بمدينة الحامول',
        ];

        return view('surveys.service', compact('centers'));
    }

    public function store(ServiceSurveyRequest $request)
    {
        $survey = ServiceSurvey::create($request->validated());

        $admins = User::role(['super_admin', 'Admin'])->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewServiceSurvey($survey));
        }

        return back()->with('success', 'شكرًا جزيلاً لمشاركتك! تقييمك يساعدنا على تحسين خدماتنا.');
    }
}
