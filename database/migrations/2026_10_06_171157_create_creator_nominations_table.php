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
        Schema::create('creator_nominations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('creator_name');
            $table->string('handle_or_url');
            $table->string('platform')->default('YouTube');
            $table->string('milestone_hint')->nullable();
            $table->text('reason')->nullable();
            $table->string('nominator_name')->nullable();
            $table->string('nominator_email')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_nominations');
    }
};
