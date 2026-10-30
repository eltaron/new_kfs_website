<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Content\Models\ExamResult;
use Illuminate\Http\Request;

class ExamResultController extends Controller
{
    public function index()
    {
        return view('exam-results.index');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'seat_number' => 'required|string|max:20',
        ], [
            'seat_number.required' => 'يرجى إدخال رقم الجلوس',
        ]);

        $result = ExamResult::where('seat_number', $request->seat_number)->first();

        if (!$result) {
            return response()->json(['not_found' => true, 'message' => 'لم يتم العثور على نتيجة بهذا الرقم']);
        }

        return response()->json([
            'not_found' => false,
            'student_name' => $result->student_name,
            'seat_number' => $result->seat_number,
            'school' => $result->school,
            'adminstration' => $result->adminstration,
            'academic_year' => $result->academic_year,
            'total_grade' => $result->total_grade,
            'subjects' => $result->subjects,
            'status' => $result->status,
        ]);
    }
}

