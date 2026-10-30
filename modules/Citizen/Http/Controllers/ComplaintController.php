<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintRequest;
use Modules\Citizen\Models\Complaint;
use App\Models\User;
use App\Notifications\NewComplaintNotification;
use App\Support\SecureUploads;
use Illuminate\Support\Facades\Notification;

class ComplaintController extends Controller
{
    public function create()
    {
        return view('complaints.create');
    }

    public function store(ComplaintRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment'] = SecureUploads::store($request->file('attachment'), 'complaints');
        }

        $complaint = Complaint::create($data);

        $admins = User::role(['super_admin', 'مسئول المركز التكنولوجي'])->get();
        Notification::send($admins, new NewComplaintNotification($complaint));

        return back()->with(
            'success',
            'تم استلام شكواك بنجاح. رقم المتابعة الخاص بك هو: ' . $complaint->id
        );
    }
}
