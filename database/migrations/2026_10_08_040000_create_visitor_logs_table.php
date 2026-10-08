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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('country_code', 5)->default('US')->index();
            $table->string('country_name', 100)->default('United States');
            $table->string('city', 100)->nullable();
            $table->string('device_type', 30)->default('Desktop')->index(); // Desktop, Mobile, Tablet
            $table->string('browser', 50)->default('Chrome');
            $table->string('os', 50)->default('macOS');
            $table->string('path', 255)->default('/');
            $table->string('referer', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
