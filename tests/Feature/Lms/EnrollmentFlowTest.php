<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingApplication;
use App\Services\Payments\PayableResolver;

class EnrollmentFlowTest extends LmsTestCase
{
    public function test_approving_an_application_creates_the_enrollment(): void
    {
        $program = $this->makeProgram(['requires_application' => true]);
        $user = $this->makeUser();

        $application = TrainingApplication::create([
            'training_program_id' => $program->id,
            'applicant_email' => 'a@example.com',
            'applicant_name' => $user->name,
            'national_id' => $user->national_id,
            'phone' => '01000000000',
            'gender' => 'ذكر',
            'educational_qualification' => 'بكالوريوس',
            'employment_status' => 'موظف',
            'current_position' => 'مهندس',
            'job_address' => 'كفر الشيخ',
            'national_id_front_image' => 'applications/f.jpg',
            'national_id_back_image' => 'applications/b.jpg',
            'has_taken_previous_courses' => false,
            'status' => 'pending',
        ]);
        $this->assertSame(0, Enrollment::where('user_id', $user->id)->count());

        $application->update(['status' => 'approved']);

        $enrollment = Enrollment::where('user_id', $user->id)->where('training_program_id', $program->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertSame('approved', $enrollment->status);
        $this->assertSame('free', $enrollment->payment_status);
        $this->assertSame($application->id, $enrollment->training_application_id);
        $this->assertSame((string) $user->id, (string) $application->fresh()->user_id);

        // Programs that require an application cannot be joined directly.
        $this->actingAs($this->makeUser())->post(route('lms.enroll', $program))->assertRedirect(route('estidama.apply', $program));
    }

    public function test_free_program_direct_enrollment(): void
    {
        $program = $this->makeProgram();
        $this->makeLesson($this->makeModule($program));
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('lms.enroll', $program))->assertRedirect(route('lms.course', $program));
        $this->actingAs($user)->post(route('lms.enroll', $program)); // idempotent

        $this->assertSame(1, Enrollment::where('user_id', $user->id)->where('training_program_id', $program->id)->count());
        $this->assertTrue(Enrollment::where('user_id', $user->id)->first()->canAccessContent());
    }

    public function test_paid_program_goes_through_the_unified_payment_service(): void
    {
        $program = $this->makeProgram(['is_paid' => true, 'price' => 150]);
        $lesson = $this->makeLesson($this->makeModule($program));
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('lms.enroll', $program))
            ->assertRedirect(route('payments.pay', ['type' => 'program_enrollment', 'id' => Enrollment::where('user_id', $user->id)->value('id')]));

        $enrollment = Enrollment::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('unpaid', $enrollment->payment_status);
        $this->assertSame('150.00', $enrollment->payableAmount());
        $this->assertTrue($enrollment->canBePaid());
        $this->assertFalse($enrollment->canAccessContent());
        $this->assertSame('program_enrollment', $enrollment->getMorphClass());
        $this->assertInstanceOf(Enrollment::class, app(PayableResolver::class)->find('program_enrollment', $enrollment->id));

        // Unpaid learners are sent back to the program page.
        $this->actingAs($user)->get(route('lms.lesson', [$program, $lesson]))->assertRedirect(route('estidama.program.show', $program));

        // The payment page starts a transaction for the owner only.
        $this->actingAs($this->makeUser())->get(route('payments.pay', ['type' => 'program_enrollment', 'id' => $enrollment->id]))->assertForbidden();
        $this->actingAs($user)->get(route('payments.pay', ['type' => 'program_enrollment', 'id' => $enrollment->id]))->assertOk();

        $enrollment->markPaid(new \Modules\Payments\Models\Transaction());
        $this->assertTrue($enrollment->fresh()->canAccessContent());
        $this->actingAs($user)->get(route('lms.lesson', [$program, $lesson]))->assertOk();
    }
}
