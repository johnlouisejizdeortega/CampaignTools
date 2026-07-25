<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads pulled from the Google Ads API `local_services_lead` resource. Keyed on
 * the Google lead id so re-syncs upsert idempotently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_leads', function (Blueprint $table) {
            $table->string('id')->primary();                 // Google lead id
            $table->string('customer_id', 16)->index();      // client account
            $table->string('lead_type')->nullable();         // MESSAGE / PHONE_CALL / BOOKING
            $table->string('category_id')->nullable();
            $table->string('service_id')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('lead_status')->nullable()->index();
            // The Google Ads API exposes whether a lead was charged, but not a
            // per-lead amount — account spend is tracked in lsa_daily_costs.
            $table->boolean('charged')->default(false)->index();
            $table->string('currency', 3)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at_google')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_leads');
    }
};
