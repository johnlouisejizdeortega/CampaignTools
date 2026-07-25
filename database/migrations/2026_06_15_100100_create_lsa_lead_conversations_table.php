<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Message / call thread entries for a lead, from
 * `local_services_lead_conversation`. Keyed on the Google conversation id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_lead_conversations', function (Blueprint $table) {
            $table->string('id')->primary();                 // Google conversation id
            $table->string('lead_id', 64)->index();          // FK -> lsa_leads.id
            $table->string('type')->nullable();              // EMAIL / MESSAGE / PHONE_CALL / SMS
            $table->text('body')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_lead_conversations');
    }
};
