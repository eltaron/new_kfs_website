<?php

namespace Modules\Citizen\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payments\Http\Controllers\PaymentController;
use Modules\Citizen\Models\Service;
use Modules\Citizen\Models\ServiceSubmission;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Services\Payments\Pricing\ServicePricing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceSubmissionController extends Controller
{
    public function store(Request $request, Service $service, ServicePricing $pricing, PaymentService $payments)
    {
        $fields = collect($service->form_fields ?? [])->filter(fn ($f) => is_array($f) && ! empty($f['name']));

        // The public form posts fields at the top level (name="area"); older
        // clients used form_data[area]. Accept both.
        $input = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $input[$name] = $field['type'] === 'file'
                ? ($request->file("form_data.$name") ?? $request->file($name))
                : $request->input("form_data.$name", $request->input($name));
        }

        validator($input, $this->rules($service, $fields->all(), $pricing), [], $fields->pluck('label', 'name')->all())->validate();

        $formData = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $value = $input[$name] ?? null;
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                // Private disk: citizen documents are never web-accessible.
                $value = $value->store('service-submissions/' . $service->getKey(), 'local');
            }
            $formData[$name] = $value;
        }

        $quote = $pricing->quote($service, $formData);
        $needsPayment = (float) $quote['total'] > 0;

        $submission = ServiceSubmission::create([
            'service_id' => $service->id,
            'user_id' => $request->user()->id,
            'submitted_data' => $formData + ['_pricing' => $quote],
            'status' => $needsPayment ? 'awaiting_payment' : 'pending',
            'payment_status' => $needsPayment ? 'pending' : 'not_required',
            'total_amount' => $quote['total'],
        ]);

        if (! $needsPayment) {
            return redirect()->route('citizen.submissions.show', $submission->id)
                ->with('success', 'تم استلام طلبك بنجاح.');
        }

        try {
            $initiation = $payments->initiate($submission);
        } catch (PaymentException $e) {
            return redirect()->route('citizen.submissions.show', $submission->id)
                ->with('error', 'تم حفظ طلبك، لكن ' . $e->getMessage() . ' يمكنك السداد لاحقاً من صفحة الطلب.');
        }

        return view('services.efinance_redirect', [
            'url' => $initiation->redirect->url,
            'params' => $initiation->redirect->fields,
            'transaction' => $initiation->transaction,
        ]);
    }

    /** Post-payment page: owner only, and only once the payment is confirmed. */
    public function success(Request $request, ServiceSubmission $submission)
    {
        abort_unless((string) $submission->user_id === (string) $request->user()->id, 403);

        if (! $submission->isPaid()) {
            return redirect()->route('citizen.submissions.show', $submission->id)
                ->with('info', 'لم يتم تأكيد السداد بعد. إذا قمت بالدفع للتو فسيتم التحديث خلال لحظات.');
        }

        return view('services.success', compact('submission'));
    }

    /**
     * Legacy route target kept for in-flight requests; the unified handler
     * lives in PaymentController@callback.
     */
    public function paymentConfirmation(Request $request)
    {
        return app(PaymentController::class)->callback($request);
    }

    private function rules(Service $service, array $fields, ServicePricing $pricing): array
    {
        $quantityField = $pricing->quantityField($service);
        $rules = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $rule = [($field['is_required'] ?? false) ? 'required' : 'nullable'];

            switch ($field['type'] ?? 'text') {
                case 'file':
                    array_push($rule, 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120');
                    break;
                case 'number':
                    array_push($rule, 'numeric', 'min:0', 'max:100000000');
                    break;
                case 'select':
                    if ($name === 'category' && ! empty($service->category_pricing)) {
                        $rule[] = Rule::in(array_column($service->category_pricing, 'name'));
                    } elseif (! empty($field['options'])) {
                        $rule[] = Rule::in(array_column($field['options'], 'value'));
                    }
                    break;
                default:
                    array_push($rule, 'string', 'max:2000');
            }

            if ($name === $quantityField) {
                // The quantity multiplies the price: never negative / non-numeric.
                $rule = [$rule[0], 'numeric', 'min:0', 'max:100000000'];
            }

            $rules[$name] = $rule;
        }

        return $rules;
    }
}
