<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only feed, streak source and XP ledger.
        Schema::create('learning_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'LESSON_STARTED', 'LESSON_COMPLETED', 'LESSON_MASTERED', 'EXERCISE_SOLVED', 'QUIZ_PASSED',
                'QUIZ_FAILED', 'LAB_COMPLETED', 'MILESTONE_COMPLETED', 'PROJECT_COMPLETED', 'SKILL_MASTERED',
                'TRACK_COMPLETED', 'BADGE_EARNED', 'CERTIFICATE_ISSUED',
            ]);
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->integer('xp')->default(0);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestamp('occurred_at');
            $table->date('occurred_on');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_on']);
        });

        DB::statement('ALTER TABLE learning_activities ADD CONSTRAINT learning_activities_xp_non_negative CHECK (xp >= 0)');
        // XP can be earned only once per user, activity and subject: repeating an action cannot farm XP.
        DB::statement('CREATE UNIQUE INDEX learning_activities_xp_once ON learning_activities (user_id, type, subject_type, subject_id) WHERE xp > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_activities');
    }
};
