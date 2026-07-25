<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit record of a single account's LSA sync run.
 */
class LsaSyncLog extends Model
{
    protected $table = 'lsa_sync_log';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'leads_synced' => 'integer',
    ];
}
