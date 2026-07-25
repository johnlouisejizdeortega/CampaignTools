<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message/call entry in a lead's conversation thread.
 *
 * @property string $id
 * @property string $lead_id
 */
class LsaLeadConversation extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LsaLead::class, 'lead_id');
    }
}
