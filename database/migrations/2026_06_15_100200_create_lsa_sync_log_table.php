<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per account per sync run — an audit trail of the scheduled job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id', 16)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('leads_synced')->default(0);
            $table->string('status')->default('running');    // running / success / error
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_sync_log');
    }
};
