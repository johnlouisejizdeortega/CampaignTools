<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per synced LSA account, holding its display name and currency so the
 * agency overview can label each client without a live API call.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsa_accounts', function (Blueprint $table) {
            $table->string('customer_id', 16)->primary();
            $table->string('name')->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsa_accounts');
    }
};
