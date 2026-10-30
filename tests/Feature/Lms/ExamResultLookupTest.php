<?php

namespace Tests\Feature\Lms;

use Modules\Content\Models\ExamResult;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** School results lookup (ExamResultController) — separate from the LMS exams. */
class ExamResultLookupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lookup_page_and_seat_number_search(): void
    {
        ExamResult::create([
            'seat_number' => 'T-990011',
            'student_name' => 'طالب اختبار',
            'school' => 'مدرسة كفر الشيخ',
            'adminstration' => 'إدارة كفر الشيخ التعليمية',
            'academic_year' => '2025/2026',
            'total_grade' => 380.5,
            'subjects' => [['name' => 'اللغة العربية', 'grade' => 75]],
            'status' => 'ناجح',
        ]);

        $this->get(route('exam-results.index'))->assertOk();

        $this->postJson(route('exam-results.lookup'), ['seat_number' => 'T-990011'])
            ->assertOk()
            ->assertJson(['not_found' => false, 'student_name' => 'طالب اختبار', 'status' => 'ناجح']);

        $this->postJson(route('exam-results.lookup'), ['seat_number' => 'NOPE-1'])
            ->assertOk()->assertJson(['not_found' => true]);

        $this->postJson(route('exam-results.lookup'), [])->assertStatus(422);
    }
}
