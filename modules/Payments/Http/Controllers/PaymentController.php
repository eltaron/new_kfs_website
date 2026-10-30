<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contracts\Payable;
use Modules\Payments\Models\Transaction;
use App\Services\Payments\CallbackResult;
use App\Services\Payments\PayableResolver;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Unified payment endpoints:
 *   pay       GET|POST /payments/pay/{type}/{id}       start (or resume) a payment
 *   checkout  GET      /payments/checkout/{type}/{id}  signed status page for guest payables
 *   callback  POST     /efinance/callback              silent server-to-server confirmation
 *   return    GET|POST /payments/return/{transaction}  where the gateway sends the browser back
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PayableResolver $resolver,
    ) {
    }

    public function pay(Request $request, string $type, string $id)
    {
        $payable = $this->resolver->find($type, $id) ?? abort(404);

        if ($response = $this->denyUnlessCanAccess($request, $payable)) {
            return $response;
        }

        if (method_exists($payable, 'isPaid') && $payable->isPaid()) {
            $paid = $payable->completedTransaction ?? null;

            return $paid
                ? redirect()->route('payments.return', $paid)->with('info', 'تم سداد هذا الطلب مسبقاً.')
                : redirect()->back()->with('info', 'تم سداد هذا الطلب مسبقاً.');
        }

        try {
            $initiation = $this->payments->initiate($payable);
        } catch (PaymentException $e) {
            return redirect()->back(fallback: $this->fallbackUrl($payable))->with('error', $e->getMessage());
        }

        return view('services.efinance_redirect', [
            'url' => $initiation->redirect->url,
            'params' => $initiation->redirect->fields,
            'transaction' => $initiation->transaction,
        ]);
    }

    /** Signed status page for guest payables (tour companies, landmark visitors). */
    public function checkout(Request $request, string $type, string $id)
    {
        $payable = $this->resolver->find($type, $id) ?? abort(404);

        if ($response = $this->denyUnlessCanAccess($request, $payable)) {
            return $response;
        }

        return view('payments.checkout', [
            'payable' => $payable,
            'payUrl' => $payable->payableOwner() === null
                ? URL::signedRoute('payments.pay', ['type' => $type, 'id' => $id])
                : route('payments.pay', ['type' => $type, 'id' => $id]),
            'lastTransaction' => $payable->transactions()->latest()->first(),
        ]);
    }

    public function callback(Request $request)
    {
        try {
            $result = $this->payments->handleCallback($request->all());
        } catch (\Throwable $e) {
            Log::error('Payment callback crashed', ['error' => $e->getMessage()]);

            // Non-000 + 500 so the gateway retries the notification later.
            return response('', 500)->header('Response_Code', '999');
        }

        Log::info('Payment callback handled', [
            'outcome' => $result->outcome,
            'reason' => $result->reason,
            'transaction' => $result->transaction?->id,
        ]);

        return response('', 200)->header('Response_Code', '000');
    }

    /**
     * The gateway may POST the browser back cross-site. That request carries no
     * session cookie (SameSite=Lax), so it runs without the session middleware
     * and just bounces to the GET route, where the citizen's session is present.
     */
    public function returnPost(Request $request, Transaction $transaction)
    {
        return redirect()->to(route('payments.return', $transaction), 303);
    }

    public function return(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== null) {
            if (! $request->user()) {
                return redirect()->guest(route('login'));
            }
            if ((string) $request->user()->getKey() !== (string) $transaction->user_id && ! Gate::allows('view', $transaction)) {
                abort(403);
            }
        }
        // Guest transactions: the (random, unguessable) UUID in the URL is the capability.

        $payable = $transaction->transactionable;

        return view('payments.result', [
            'transaction' => $transaction,
            'payable' => $payable instanceof Payable ? $payable : null,
            'receiptUrl' => $transaction->isCompleted() ? ReceiptController::urlFor($transaction) : null,
            'retryUrl' => ($payable instanceof Payable && method_exists($payable, 'canBePaid') && $payable->canBePaid())
                ? $payable->paymentUrl()
                : null,
        ]);
    }

    /**
     * Owner-bound payables require the owner to be logged in; guest payables
     * require a valid signed URL.
     */
    private function denyUnlessCanAccess(Request $request, Model&Payable $payable)
    {
        $owner = $payable->payableOwner();

        if ($owner === null) {
            return $request->hasValidSignature() ? null : abort(403, 'رابط الدفع غير صالح أو منتهي.');
        }

        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        return (string) $request->user()->getKey() === (string) $owner->getKey() ? null : abort(403);
    }

    private function fallbackUrl(Model&Payable $payable): string
    {
        return $payable->payableOwner() === null ? $payable->checkoutUrl() : route('citizen.dashboard');
    }
}
