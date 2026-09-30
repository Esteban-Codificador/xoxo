<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quizzes (§35, ADR-036): one per lesson, graded on the server. An attempt
 * stores the questions it showed, in order, so editing the quiz never
 * changes an attempt in progress; its time runs on the server (expires_at).
 * Answers are rows, so "most failed questions" is a GROUP BY, not JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            // RESTRICT: content with learner history is archived, never deleted.
            $table->foreignId('lesson_id')->unique()->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->string('description', 1000)->nullable();
            $table->smallInteger('pass_threshold')->default(70);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->smallInteger('max_attempts')->nullable();
            $table->boolean('shuffle_questions')->default(true);
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE quizzes ADD CONSTRAINT quizzes_pass_threshold_check CHECK (pass_threshold BETWEEN 1 AND 100)');
        DB::statement('ALTER TABLE quizzes ADD CONSTRAINT quizzes_time_limit_check CHECK (time_limit_seconds IS NULL OR time_limit_seconds BETWEEN 60 AND 10800)');
        DB::statement('ALTER TABLE quizzes ADD CONSTRAINT quizzes_max_attempts_check CHECK (max_attempts IS NULL OR max_attempts BETWEEN 1 AND 100)');

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['SINGLE_CHOICE', 'MULTIPLE_CHOICE', 'TRUE_FALSE', 'ORDERING', 'MATCHING']);
            $table->jsonb('prompt');
            // By type: options with the correct ones, items in order or pairs (QuestionTypes).
            $table->jsonb('payload');
            $table->jsonb('explanation');
            $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])->nullable();
            $table->smallInteger('points')->default(1);
            $table->smallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['quiz_id', 'position']);
        });

        DB::statement('ALTER TABLE quiz_questions ADD CONSTRAINT quiz_questions_points_check CHECK (points BETWEEN 1 AND 10)');

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->smallInteger('attempt_number');
            // Question ids in the order this attempt showed them.
            $table->jsonb('questions');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('timed_out')->default(false);
            $table->smallInteger('score')->nullable();
            $table->smallInteger('points_earned')->nullable();
            $table->smallInteger('points_total')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quiz_id', 'attempt_number']);
            $table->index(['quiz_id', 'passed']);
        });

        // At most one open attempt per learner and quiz: a double click resumes it.
        DB::statement('CREATE UNIQUE INDEX quiz_attempts_one_open ON quiz_attempts (user_id, quiz_id) WHERE submitted_at IS NULL');
        DB::statement('ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_score_check CHECK (score IS NULL OR score BETWEEN 0 AND 100)');
        DB::statement('ALTER TABLE quiz_attempts ADD CONSTRAINT quiz_attempts_graded_check CHECK (submitted_at IS NULL OR (score IS NOT NULL AND passed IS NOT NULL))');

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            // CASCADE: a question removed from the quiz takes its answers along;
            // the attempts keep the score they got.
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            // NULL: not answered.
            $table->jsonb('answer')->nullable();
            $table->boolean('is_correct');
            $table->smallInteger('points_awarded');

            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
            $table->index(['quiz_question_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
