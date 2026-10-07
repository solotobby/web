<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_prospects', function (Blueprint $table) {
            $table->string('email_type')->nullable()->after('email_status');
            $table->string('email_source')->nullable()->after('email_type');
            $table->string('outreach_readiness')->nullable()->after('email_source');
            $table->text('personalised_email')->nullable()->after('email_subject');
        });
    }

    public function down(): void
    {
        Schema::table('creator_prospects', function (Blueprint $table) {
            $table->dropColumn([
                'email_type',
                'email_source',
                'outreach_readiness',
                'personalised_email',
            ]);
        });
    }
};
