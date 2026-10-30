<?php

namespace Modules\Syndicate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** سجل تدقيق لكل استدعاء على واجهة برمجة النقابة. */
class ApiLog extends Model
{
    protected $table = 'syndicate_api_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'integration_account_id', 'token_id', 'method', 'path', 'route_name', 'ip', 'status_code',
        'duration_ms', 'idempotency_key', 'request_body', 'error_code', 'user_agent',
    ];

    protected $casts = ['request_body' => 'array'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(IntegrationAccount::class, 'integration_account_id');
    }
}
