<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string EMPTY_RICH_CONTENT = '{"version": 1, "doc": {"type": "doc", "content": []}}';

    private const array LESSON_CONTENT_TYPES = ['CONCEPT', 'TUTORIAL', 'READING', 'VIDEO', 'DOCUMENTATION', 'CHALLENGE'];

    private const array DIFFICULTIES = ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'];

    public function up(): void
    {
        // Working copy: what editors change. Learners only ever read lesson_versions.
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->string('slug', 160)->unique();
            $table->string('title', 200);
            $table->text('summary');
            $table->text('why_it_matters');
            $table->jsonb('learning_objectives')->default(DB::raw("'[]'::jsonb"));
            $table->jsonb('body')->default(DB::raw("'".self::EMPTY_RICH_CONTENT."'::jsonb"));
            $table->enum('content_type', self::LESSON_CONTENT_TYPES)->default('CONCEPT');
            $table->enum('difficulty', self::DIFFICULTIES)->default('BEGINNER');
            $table->smallInteger('estimated_minutes')->default(30);
            $table->smallInteger('position')->default(0);
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['module_id', 'position']);
            $table->index('status');
            $table->index('published_version_id');
        });

        DB::statement('ALTER TABLE lessons ADD CONSTRAINT lessons_estimated_minutes_range CHECK (estimated_minutes BETWEEN 1 AND 600)');

        // Immutable snapshots created on publish (ADR-006).
        Schema::create('lesson_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->integer('version');
            $table->string('title', 200);
            $table->text('summary');
            $table->text('why_it_matters');
            $table->jsonb('learning_objectives');
            $table->jsonb('body');
            $table->enum('content_type', self::LESSON_CONTENT_TYPES);
            $table->enum('difficulty', self::DIFFICULTIES);
            $table->smallInteger('estimated_minutes');
            $table->char('content_hash', 64);
            $table->string('change_note', 500)->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['lesson_id', 'version']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('published_version_id')->references('id')->on('lesson_versions')->nullOnDelete();
        });

        Schema::create('lesson_dependencies', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->enum('kind', ['REQUIRED', 'RECOMMENDED'])->default('REQUIRED');

            $table->primary(['lesson_id', 'prerequisite_lesson_id']);
            $table->index('prerequisite_lesson_id');
        });

        DB::statement('ALTER TABLE lesson_dependencies ADD CONSTRAINT lesson_dependencies_not_self CHECK (lesson_id <> prerequisite_lesson_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_dependencies');

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['published_version_id']);
        });

        Schema::dropIfExists('lesson_versions');
        Schema::dropIfExists('lessons');
    }
};
