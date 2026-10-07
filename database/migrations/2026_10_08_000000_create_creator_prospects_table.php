<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_prospects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('prospect_number')->unique();
            $table->string('creator');
            $table->string('speciality')->nullable()->index();
            $table->text('primary_outreach_angle')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('public_email')->nullable();
            $table->string('email_status')->nullable();
            $table->text('contact_url')->nullable();
            $table->string('recommended_priority')->nullable()->index();
            $table->string('email_subject')->nullable();
            $table->string('status')->default('Not contacted')->index();
            $table->text('personalisation_note')->nullable();
            $table->string('email')->nullable()->index();
            $table->text('internal_notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_prospects');
    }
};
