<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postcards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('number')->unique();
            $table->string('name');
            $table->string('location');
            $table->string('teaser', 72);
            $table->date('addressed_to');
            $table->timestamp('sealed_at')->useCurrent();
            $table->boolean('founding')->default(true);
            $table->foreignUuid('creator_id')->nullable()->constrained('creators')->nullOnDelete();
            $table->boolean('seeded')->default(false);
            $table->timestamps();

            $table->index('sealed_at');
            $table->index('addressed_to');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postcards');
    }
};
