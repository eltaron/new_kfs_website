<?php

namespace Tests\Feature\Lms;

use Modules\Estidama\Models\TrainingApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicEstidamaPagesTest extends LmsTestCase
{
    public function test_public_pages_render(): void
    {
        $program = $this->makeProgram(['requires_application' => true]);
        $this->makeLesson($this->makeModule($program));

        $this->get(route('estidama.index'))->assertOk();
        $this->get(route('estidama.programs'))->assertOk()->assertSee(route('estidama.program.show', $program), false);
        $this->get(route('estidama.program.show', $program))->assertOk()->assertSee('سجّل الدخول للالتحاق');
        $this->get(route('lms.certificates.verify'))->assertOk();

        $user = $this->makeUser();
        $this->actingAs($user)->get(route('estidama.program.show', $program))->assertOk()->assertSee('تقديم طلب الالتحاق');
        $this->actingAs($user)->get(route('estidama.apply', $program))->assertOk();
    }

    public function test_application_is_stored_privately_and_linked_to_the_account(): void
    {
        Storage::fake('local');
        $program = $this->makeProgram(['requires_application' => true]);
        $user = $this->makeUser();
        $this->makeUser('estidama_admin'); // receives the panel notification

        $this->actingAs($user)->post(route('estidama.storeApplication', $program), [
            'applicant_email' => 'applicant@example.com',
            'applicant_name' => 'مقدم الطلب',
            'national_id' => $user->national_id,
            'phone' => '01012345678',
            'gender' => 'ذكر',
            'educational_qualification' => 'بكالوريوس',
            'employment_status' => 'موظف',
            'current_position' => 'أخصائي',
            'job_address' => 'كفر الشيخ',
            'national_id_front_image' => UploadedFile::fake()->image('front.jpg'),
            'national_id_back_image' => UploadedFile::fake()->image('back.jpg'),
            'has_taken_previous_courses' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $application = TrainingApplication::where('training_program_id', $program->id)->firstOrFail();
        $this->assertSame((string) $user->id, (string) $application->user_id);
        Storage::disk('local')->assertExists($application->national_id_front_image);

        $this->actingAs($user)->get(route('estidama.program.show', $program))->assertSee('طلب الالتحاق قيد المراجعة');
    }
}
