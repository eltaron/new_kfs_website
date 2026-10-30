<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuggestionRequest;
use Modules\Citizen\Models\Suggestion;
use App\Models\User;
use App\Notifications\NewSuggestionSubmitted;
use Illuminate\Support\Facades\Notification;

class SuggestionController extends Controller
{
    public function create()
    {
        return view('suggestions.create');
    }

    public function store(SuggestionRequest $request)
    {
        $suggestion = Suggestion::create($request->validated());

        $admins = User::role(['super_admin', 'Admin'])->get();
        Notification::send($admins, new NewSuggestionSubmitted($suggestion));

        return back()->with('success', 'شكرًا لك! تم استلام مقترحك بنجاح ونقدر مساهمتك.');
    }
}
