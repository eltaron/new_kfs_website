<?php

namespace Modules\Estidama\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Estidama\Models\EstidamaEvent;
use Modules\Estidama\Models\PartnerLogo;
use Modules\Content\Models\Setting;
use Modules\Estidama\Models\TrainingProgram;
use Modules\Estidama\Models\Enrollment;
use Modules\Estidama\Models\TrainingApplication;
use App\Models\User;
use App\Notifications\NewTrainingApplication;
use App\Support\SecureUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class EstidamaController extends Controller
{
    public function index()
    {
        // 1. Get Settings (already available globally via ComposerServiceProvider, but we can call it here for clarity)
        $settings = Setting::pluck('value', 'key');

        // 2. Statistics
        // Use `query()` for a cleaner count on the same line.
        $programsCount = 420;//TrainingProgram::query()->count();

        // Count only users who have a status of 'completed' in their enrollments
        $traineesCount = 9912;//Enrollment::where('status', 'completed')->distinct('user_id')->count('user_id');

        // 3. Latest 'Open' Training Programs
        $trainingPrograms = TrainingProgram::with('trainingCenter') // Eager load the center name
            ->where('status', 'open')
            ->latest('start_date')
            ->take(3)
            ->get();

        // 4. Estidama Events Slider
        $events = EstidamaEvent::latest()->take(8)->get(); // Taking 8 for a smoother loop in swiper

        // 5. Partners (Accreditations and Strategic Partners)
        $partners = PartnerLogo::orderBy('created_at')->get();

        // 6. Return the view and pass all the data to it
        return view('estidama.index', compact(
            'settings',
            'programsCount',
            'traineesCount',
            'trainingPrograms',
            'events',
            'partners'
        ));
    }
    public function programs()
    {
        // Fetch all programs, order by start date, and paginate
        $trainingPrograms = TrainingProgram::with('trainingCenter')
            ->latest('start_date')
            ->paginate(9); // Show 9 programs per page

        return view('estidama.programs', [
            'trainingPrograms' => $trainingPrograms
        ]);
    }
    public function apply(TrainingProgram $program)
    {
        // Abort if the program is not open for registration
        if ($program->status !== 'open') {
            abort(404, 'Registration for this program is currently closed.');
        }

        return view('estidama.apply', [
            'program' => $program
        ]);
    }

    public function storeApplication(Request $request, TrainingProgram $program)
    {
        // Validation rules based on the form from the image
        $validated = $request->validate([
            'applicant_email' => 'required|email|max:255',
            'applicant_name' => 'required|string|max:255',
            'national_id' => 'required|string|digits:14|unique:training_applications,national_id,NULL,id,training_program_id,' . $program->id,
            'phone' => 'required|string|max:20',
            'gender' => 'required|string|in:ذكر,أنثى',
            'educational_qualification' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'highest_degree' => 'nullable|string|max:255',
            'employment_status' => 'required|string|max:255',
            'current_position' => 'required|string|max:255',
            'job_address' => 'required|string|max:255',
            'national_id_front_image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048', // 2MB Max
            'national_id_back_image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048',
            'personal_statement' => 'nullable|file|mimes:pdf|extensions:pdf|max:2048',
            'has_taken_previous_courses' => 'required|boolean',
            'previous_courses_names' => 'nullable|string|max:2000',
        ]);

        // Handle File Uploads
        $validated['national_id_front_image'] = SecureUploads::store($request->file('national_id_front_image'), 'applications');
        $validated['national_id_back_image'] = SecureUploads::store($request->file('national_id_back_image'), 'applications');
        if ($request->hasFile('personal_statement')) {
            $validated['personal_statement'] = SecureUploads::store($request->file('personal_statement'), 'applications');
        }

        // Add the program ID and link the application to the signed-in account
        $validated['training_program_id'] = $program->id;
        $validated['user_id'] = $request->user()?->getKey();

        $application = TrainingApplication::create($validated);

        // Notify Estidama staff (database notifications in the panel)
        $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'Admin', 'estidama_admin']))->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewTrainingApplication($application));
        }

        return back()->with('success', 'تم استلام طلبك بنجاح! سيتم مراجعته وإعلامك بالنتيجة.');
    }
}
