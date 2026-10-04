<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envelopes', function (Blueprint $table) {
            $table->foreignUuid('postcard_id')->primary()->constrained('postcards')->cascadeOnDelete();
            $table->string('letter', 600);
            $table->string('email');
            $table->string('photo_path')->nullable();
            $table->json('predictions')->nullable();
            $table->string('claim_token_hash')->nullable()->index();
            $table->uuid('author_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envelopes');
    }
};
