<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_registration_works_even_when_citizen_role_is_missing(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Role::where('name', 'citizen')->where('guard_name', 'web')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->post(route('register.citizen.store'), [
            'name' => 'أحمد محمد علي حسن',
            'email' => 'register-test@demo.kfs.invalid',
            'national_id' => '29001011500017',
            'phone' => '01012345678',
            'job_title' => 'مهندس',
            'address' => 'كفر الشيخ',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'national_id_image' => UploadedFile::fake()->image('id.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user = User::where('email', 'register-test@demo.kfs.invalid')->firstOrFail();
        $this->assertTrue($user->hasRole('citizen'));
    }
}
