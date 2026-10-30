<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SyndicateCertificate;
use Illuminate\Http\Request;
use Modules\Syndicate\Models\Project;
use Modules\Syndicate\Models\SyndicateRequest;

/**
 * الاستعلام العام عن قرار / شهادة الإشراف (مسار throttle:10,1 في routes/web.php).
 *
 * يقبل:
 *  - الرقم القومي للمالك (14 رقماً)  ⇒ مشروعاته وحالة الإشراف على كل منها
 *  - رقم شهادة (SYN-SUP-… / SYN-CAN-…) ⇒ تحويل لصفحة التحقق العامة
 *  - رقم معاملة (SYN-REQ-… / SYN-CRQ-…) ⇒ حالة المعاملة
 *
 * لا تُعرض البيانات الشخصية الحساسة (العناوين / الأرقام القومية) في النتائج.
 */
class CitizenLookupController extends Controller
{
    public function index()
    {
        return view('lookup.index');
    }

    public function search(Request $request)
    {
        // التوافق مع النموذج القديم (national_id) والجديد (q).
        $query = strtoupper(trim((string) ($request->input('q') ?? $request->input('national_id'))));
        $request->merge(['q' => $query]);
        $request->validate([
            'q' => ['required', 'string', 'max:40', 'regex:/^(\d{14}|[A-Z0-9\-]{6,40})$/'],
        ], [
            'q.required' => 'أدخل الرقم القومي أو رقم الشهادة أو رقم المعاملة.',
            'q.regex' => 'أدخل رقماً قومياً صحيحاً (14 رقماً) أو رقم شهادة / معاملة صحيحاً.',
        ]);

        if (! ctype_digit($query)) {
            if (SyndicateCertificate::where('number', $query)->exists()) {
                return redirect()->route('syndicate.verify.show', ['number' => $query]);
            }

            $transaction = SyndicateRequest::with(['project', 'certificate'])->where('transaction_number', $query)->first();

            return view('lookup.index', [
                'searched' => $query,
                'transaction' => $transaction,
                'notFound' => ! $transaction,
            ]);
        }

        $projects = Project::with('activeCertificate')
            ->where('owner_national_id', $query)
            ->latest('updated_at')->limit(20)->get();

        // شهادات قديمة غير مرتبطة بمشروع (قبل منظومة المشروعات).
        $legacy = SyndicateCertificate::whereNull('project_id')->where('owner_national_id', $query)->latest('id')->limit(10)->get();

        return view('lookup.index', [
            'searched' => $query,
            'projects' => $projects,
            'legacy' => $legacy,
            'notFound' => $projects->isEmpty() && $legacy->isEmpty(),
        ]);
    }
}
