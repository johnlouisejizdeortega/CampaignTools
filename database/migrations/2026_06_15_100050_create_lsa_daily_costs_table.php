<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily LSA campaign cost per account, from campaign metrics. The lead resource
 * has no per-lead amount, so account spend (and cost-per-lead) is derived from
 * this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_daily_costs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id', 16);
            $table->date('date');
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('currency', 3)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_daily_costs');
    }
};
