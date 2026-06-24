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
        Schema::create('score_cache', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('sex');
            $table->unsignedTinyInteger('age_min');
            $table->unsignedTinyInteger('age_max');
            $table->decimal('single_ratio', 8, 6);
            $table->unsignedInteger('single_count');
            $table->unsignedInteger('total_count');
            $table->decimal('score', 5, 2);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique(['region_id', 'sex', 'age_min', 'age_max'], 'score_cache_unique');
            $table->index(['sex', 'age_min', 'age_max', 'score'], 'score_cache_ranking_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_cache');
    }
};
