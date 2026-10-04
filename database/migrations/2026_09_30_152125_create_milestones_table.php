<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('creator_id');
            $table->string('title');
            $table->date('unlock_date');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('creators')->cascadeOnDelete();
        });

        Schema::table('postcards', function (Blueprint $table) {
            $table->uuid('milestone_id')->nullable()->after('creator_id');
            $table->foreign('milestone_id')->references('id')->on('milestones')->nullOnDelete();
        });

        // Backfill existing creator milestones into milestones table
        $creators = DB::table('creators')->whereNotNull('milestone_title')->where('milestone_title', '!=', '')->get();
        foreach ($creators as $creator) {
            $milestoneId = (string) Str::uuid();
            DB::table('milestones')->insert([
                'id' => $milestoneId,
                'creator_id' => $creator->id,
                'title' => $creator->milestone_title,
                'unlock_date' => $creator->unlock_date ?? '2028-01-01',
                'description' => $creator->bio,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('postcards')->where('creator_id', $creator->id)->update([
                'milestone_id' => $milestoneId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postcards', function (Blueprint $table) {
            $table->dropForeign(['milestone_id']);
            $table->dropColumn('milestone_id');
        });

        Schema::dropIfExists('milestones');
    }
};
