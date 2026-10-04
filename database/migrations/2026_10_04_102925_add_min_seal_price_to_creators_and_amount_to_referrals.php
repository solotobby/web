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
            $table->unsignedInteger('min_seal_price_cents')->default(500);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->unsignedInteger('amount_cents')->default(500);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table) {
            $table->dropColumn('min_seal_price_cents');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('amount_cents');
        });
    }
};
