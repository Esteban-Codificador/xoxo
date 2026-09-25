<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only IN_PROGRESS, COMPLETED and MASTERED are stored; LOCKED and AVAILABLE are computed (ADR-007).
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // RESTRICT: content with learner history is archived, never deleted.
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED', 'MASTERED']);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->foreignId('completed_version_id')->nullable()->constrained('lesson_versions')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'status']);
            $table->index(['lesson_id', 'status']);
        });

        DB::statement("ALTER TABLE lesson_progress ADD CONSTRAINT lesson_progress_completion_date CHECK (status = 'IN_PROGRESS' OR completed_at IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
