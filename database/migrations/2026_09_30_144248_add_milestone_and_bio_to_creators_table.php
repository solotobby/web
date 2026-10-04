<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('platform');
            $table->string('milestone_title')->nullable()->after('bio');
            $table->date('unlock_date')->nullable()->after('milestone_title');
            $table->string('avatar_url')->nullable()->after('unlock_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            $table->dropColumn(['bio', 'milestone_title', 'unlock_date', 'avatar_url']);
        });
    }
};
