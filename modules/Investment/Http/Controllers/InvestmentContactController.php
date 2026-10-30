<?php

namespace Modules\Investment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Investment\Models\InvestmentMessage;
use Illuminate\Http\Request;

class InvestmentContactController extends Controller
{
    /**
     * عرض صفحة تواصل معنا للاستثمار
     */
    public function create()
    {
        return view('investments.contact');
    }

    /**
     * حفظ رسالة المستثمر في الداتابيز
     */
    public function store(Request $request)
    {
        // التحقق من صحة البيانات
        $validatedData = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'required|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'subject'      => 'required|string|max:255',
            'message'      => 'required|string|min:10',
        ], [
            'name.required'    => 'يرجى إدخال الاسم بالكامل',
            'email.required'   => 'البريد الإلكتروني مطلوب للتواصل',
            'email.email'      => 'يرجى إدخال بريد إلكتروني صحيح',
            'phone.required'   => 'رقم الهاتف ضروري لتسهيل التواصل معك',
            'subject.required' => 'يرجى تحديد موضوع استفسارك',
            'message.required' => 'يرجى كتابة محتوى الرسالة',
        ]);

        // تخزين البيانات في الجدول المنفصل
        InvestmentMessage::create($validatedData);

        // العودة مع رسالة نجاح فخمة
        return back()->with('success', 'تم استلام استفساركم بنجاح. سيقوم المكتب الفني لإدارة الاستثمار بمراجعة رسالتكم والتواصل معكم في أقرب وقت ممكن.');
    }
}
