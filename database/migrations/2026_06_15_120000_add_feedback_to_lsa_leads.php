<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks whether quality feedback was submitted to Google for a lead (so the UI
 * can show it as reported immediately, without waiting for the next sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lsa_leads', function (Blueprint $table) {
            $table->boolean('feedback_submitted')->default(false)->after('charged');
            $table->string('feedback_reason')->nullable()->after('feedback_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('lsa_leads', function (Blueprint $table) {
            $table->dropColumn(['feedback_submitted', 'feedback_reason']);
        });
    }
};
