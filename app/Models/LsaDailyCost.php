<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Daily Local Services campaign cost for an account.
 *
 * @property string $customer_id
 */
class LsaDailyCost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'cost' => 'decimal:2',
        'synced_at' => 'datetime',
    ];
}
