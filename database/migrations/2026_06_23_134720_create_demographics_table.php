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
        Schema::create('demographics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('sex');
            $table->unsignedTinyInteger('age_min');
            $table->unsignedTinyInteger('age_max');
            $table->string('household_status');
            $table->unsignedInteger('count');
            $table->timestamps();

            $table->unique(['region_id', 'year', 'sex', 'age_min', 'age_max', 'household_status'], 'demographics_unique');
            $table->index(['year', 'sex', 'age_min', 'age_max', 'household_status'], 'demographics_query_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demographics');
    }
};
