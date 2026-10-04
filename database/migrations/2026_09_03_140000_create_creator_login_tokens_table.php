<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::create('creator_login_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('creators')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['creator_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_login_tokens');

        Schema::table('creators', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }
};
