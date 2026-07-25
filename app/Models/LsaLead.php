<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Local Services Ads lead cached from the Google Ads API.
 *
 * @property string $id
 * @property string $customer_id
 * @property bool $charged
 */
class LsaLead extends Model
{
    // The primary key is the Google lead id (a string), not auto-incrementing.
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'charged' => 'boolean',
        'charge_amount' => 'decimal:2',
        'created_at_google' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(LsaLeadConversation::class, 'lead_id')->orderBy('occurred_at');
    }
}
