<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageRequest;
use Modules\Citizen\Models\ContactMessage;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact.index');
    }

    public function store(ContactMessageRequest $request)
    {
        ContactMessage::create($request->validated());

        return back()->with('success', 'شكرًا لك! تم استلام رسالتك بنجاح وسنقوم بالرد في أقرب وقت.');
    }
}
