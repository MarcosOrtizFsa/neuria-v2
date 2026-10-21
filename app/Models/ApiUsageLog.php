<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['request_id', 'media_outlet_id', 'media_api_credential_id', 'endpoint', 'method', 'status_code', 'records_returned', 'duration_ms', 'result', 'ip_hash'])]
class ApiUsageLog extends Model
{
    public function mediaOutlet(): BelongsTo
    {
        return $this->belongsTo(MediaOutlet::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(MediaApiCredential::class, 'media_api_credential_id');
    }
}
