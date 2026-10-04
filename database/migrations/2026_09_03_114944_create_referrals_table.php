<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('postcard_id')->unique()->constrained('postcards')->cascadeOnDelete();
            $table->foreignUuid('creator_id')->constrained('creators')->cascadeOnDelete();
            $table->unsignedInteger('cut_cents')->default(150);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['creator_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
