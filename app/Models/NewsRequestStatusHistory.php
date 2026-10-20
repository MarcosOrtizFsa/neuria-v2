<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_request_id', 'user_id', 'from_status', 'to_status', 'reason'])]
class NewsRequestStatusHistory extends Model
{
    public function newsRequest(): BelongsTo
    {
        return $this->belongsTo(NewsRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
