<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payments\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class ReceiptController extends Controller
{
    /**
     * Printable receipt for a completed payment. Allowed for:
     *  - the paying citizen (transaction owner),
     *  - staff allowed to view transactions (TransactionPolicy / super_admin),
     *  - anyone holding a signed receipt link (guest payers).
     */
    public function print(Request $request, Transaction $transaction)
    {
        $user = $request->user();

        $allowed = $request->hasValidSignature()
            || ($user && $transaction->user_id !== null && (string) $user->getKey() === (string) $transaction->user_id)
            || ($user && Gate::forUser($user)->allows('view', $transaction));

        if (! $allowed) {
            return $user ? abort(403) : redirect()->guest(route('login'));
        }

        abort_unless($transaction->isCompleted(), 404, 'لا يوجد إيصال لمعاملة غير مكتملة.');

        $transaction->load(['user', 'transactionable']);

        return view('receipt.print', ['transaction' => $transaction]);
    }

    /** Receipt link to show to the payer: plain for owners, signed for guests. */
    public static function urlFor(Transaction $transaction): string
    {
        return $transaction->user_id === null
            ? URL::signedRoute('receipt.print', $transaction)
            : route('receipt.print', $transaction);
    }
}
