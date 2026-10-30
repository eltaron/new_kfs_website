<?php

namespace Modules\Syndicate\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Syndicate\Models\Certificate;
use Modules\Syndicate\Models\SyndicateRequest;

/** حدث نطاق (Domain event) يُطلق بعد نجاح المعاملة (commit). */
class SyndicateRequestSubmitted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public SyndicateRequest $request)
    {
    }
}
