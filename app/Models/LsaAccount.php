<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A synced Local Services account (client), with its display name and currency.
 *
 * @property string $customer_id
 */
class LsaAccount extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'customer_id';

    protected $guarded = [];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
