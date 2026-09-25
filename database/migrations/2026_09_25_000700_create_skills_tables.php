<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 160);
            $table->text('description');
            $table->string('icon', 64)->nullable();
            $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])->default('BEGINNER');
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_dependencies', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_skill_id')->constrained('skills')->cascadeOnDelete();
            $table->enum('kind', ['REQUIRED', 'RECOMMENDED'])->default('REQUIRED');
            $table->smallInteger('min_progress')->default(70);

            $table->primary(['skill_id', 'prerequisite_skill_id']);
            $table->index('prerequisite_skill_id');
        });

        DB::statement('ALTER TABLE skill_dependencies ADD CONSTRAINT skill_dependencies_not_self CHECK (skill_id <> prerequisite_skill_id)');
        DB::statement('ALTER TABLE skill_dependencies ADD CONSTRAINT skill_dependencies_min_progress_range CHECK (min_progress BETWEEN 1 AND 100)');

        Schema::create('lesson_skill', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('weight')->default(1);

            $table->primary(['lesson_id', 'skill_id']);
            $table->index('skill_id');
        });

        DB::statement('ALTER TABLE lesson_skill ADD CONSTRAINT lesson_skill_weight_range CHECK (weight BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_skill');
        Schema::dropIfExists('skill_dependencies');
        Schema::dropIfExists('skills');
    }
};
