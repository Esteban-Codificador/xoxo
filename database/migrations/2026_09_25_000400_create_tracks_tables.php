<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roadmap_id')->constrained()->restrictOnDelete();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->text('summary');
            $table->jsonb('description')->nullable();
            $table->text('why_it_matters');
            $table->string('icon', 64)->nullable();
            $table->smallInteger('position')->default(0);
            $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])->default('BEGINNER');
            $table->smallInteger('estimated_hours')->nullable();
            $table->smallInteger('review_interval_months')->nullable();
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['roadmap_id', 'slug']);
            $table->index(['roadmap_id', 'position']);
            $table->index('status');
        });

        Schema::create('track_dependencies', function (Blueprint $table) {
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_track_id')->constrained('tracks')->cascadeOnDelete();
            $table->enum('kind', ['REQUIRED', 'RECOMMENDED'])->default('REQUIRED');
            $table->smallInteger('min_progress')->default(100);

            $table->primary(['track_id', 'prerequisite_track_id']);
            $table->index('prerequisite_track_id');
        });

        DB::statement('ALTER TABLE track_dependencies ADD CONSTRAINT track_dependencies_not_self CHECK (track_id <> prerequisite_track_id)');
        DB::statement('ALTER TABLE track_dependencies ADD CONSTRAINT track_dependencies_min_progress_range CHECK (min_progress BETWEEN 1 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('track_dependencies');
        Schema::dropIfExists('tracks');
    }
};
