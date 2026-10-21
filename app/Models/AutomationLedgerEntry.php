<?php

namespace App\Models;

use Database\Factories\AutomationLedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'automation_run_id', 'automation_quote_id', 'type', 'amount_minor', 'currency', 'reason', 'metadata'])]
class AutomationLedgerEntry extends Model
{
    /** @use HasFactory<AutomationLedgerEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationRun::class, 'automation_run_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(AutomationQuote::class, 'automation_quote_id');
    }
}
