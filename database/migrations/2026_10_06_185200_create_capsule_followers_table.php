<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capsule_followers', function (Blueprint $table) {
            $table->id();
            $table->uuid('creator_id')->index();
            $table->string('email')->index();
            $table->uuid('milestone_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('creators')->cascadeOnDelete();
            $table->unique(['creator_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capsule_followers');
    }
};
