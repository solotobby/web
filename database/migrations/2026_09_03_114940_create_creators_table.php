<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('handle');
            $table->string('slug')->unique();
            $table->string('platform')->default('TikTok');
            $table->string('email');
            $table->string('stripe_connect_id')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creators');
    }
};
