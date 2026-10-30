<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidNationalID;
use App\Support\SecureUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterCitizenController extends Controller
{
    public function create()
    {
        return view('auth.register-citizen');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^([\p{Arabic}]+)\s([\p{Arabic}]+)\s([\p{Arabic}]+)\s([\p{Arabic}]+)$/u',
                ],

                'national_id' => ['required', 'string', 'unique:users,national_id', new ValidNationalID],
                'phone' => 'required|string|unique:users,phone|max:14',
                'email' => 'nullable|string|email|max:255|unique:users,email',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->mixedCase()->numbers()->symbols(),
                ],
                'job_title' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'national_id_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ],
            [
                'name.regex' => 'الاسم يجب أن يكون رباعي وبالحروف العربية فقط (مثال: أحمد محمد علي حسن)',

                'name.required' => 'الاسم مطلوب',
                'name.max' => 'الاسم لا يجب أن يزيد عن 255 حرفًا',

                'national_id.required' => 'الرقم القومي مطلوب',
                'national_id.unique' => 'الرقم القومي مسجل بالفعل',

                'phone.required' => 'رقم الهاتف مطلوب',
                'phone.unique' => 'رقم الهاتف مسجل بالفعل',
                'phone.max' => 'رقم الهاتف لا يجب أن يزيد عن 14 رقمًا',

                'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
                'email.unique' => 'البريد الإلكتروني مسجل بالفعل',

                'password.required' => 'كلمة المرور مطلوبة',
                'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
                'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
                'password.mixed' => 'كلمة المرور يجب أن تحتوي على حروف كبيرة وصغيرة',
                'password.numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل',
                'password.symbols' => 'كلمة المرور يجب أن تحتوي على رمز خاص',

                'national_id_image.required' => 'صورة الرقم القومي مطلوبة',
                'national_id_image.image' => 'الملف المرفوع يجب أن يكون صورة',
                'national_id_image.mimes' => 'الصورة يجب أن تكون بصيغة jpg أو png',
                'national_id_image.max' => 'حجم الصورة لا يجب أن يزيد عن 2 ميجا',
            ]
        );


        // National-ID images are sensitive: store on the private disk (see App\Support\SecureUploads).
        $path = SecureUploads::store($request->file('national_id_image'), 'national_ids');

        $user = DB::transaction(function () use ($validated, $path) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'national_id' => $validated['national_id'],
                'phone' => $validated['phone'],
                'job_title' => $validated['job_title'],
                'address' => $validated['address'],
                'national_id_image' => $path,
                'status' => 'pending', // Account is pending approval by default
            ]);

            // Role is created on demand so a fresh/partially seeded DB never breaks registration.
            $user->assignRole(Role::findOrCreate((string) (config('security.login.citizen_roles')[0] ?? 'citizen'), 'web'));

            return $user;
        });

        // Pending accounts must be approved before they can sign in (see LoginCitizenController).
        if (LoginCitizenController::statusBlockMessage($user) !== null) {
            return redirect()->route('login')->with('success', 'تم إنشاء حسابك بنجاح، وهو الآن قيد المراجعة. سيتم إخطارك فور تفعيله.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('citizen.dashboard'));
    }
}
