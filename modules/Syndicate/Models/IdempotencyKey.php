<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $table = 'syndicate_idempotency_keys';

    protected $fillable = [
        'integration_account_id', 'key', 'method', 'path', 'request_hash', 'response_status', 'response_body',
    ];
}
