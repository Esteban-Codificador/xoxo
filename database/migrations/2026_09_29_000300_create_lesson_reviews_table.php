<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Review flow of lessons (architecture §7, ADR-032): each submission is a
 * row, resolved when an editor publishes or returns it, or its author
 * withdraws it. At most one open review per lesson.
 */
return new class extends Migration
{
    private const array ACTIONS = [
        'CREATED', 'UPDATED', 'DELETED', 'SUBMITTED', 'PUBLISHED', 'UNPUBLISHED',
        'ARCHIVED', 'RESTORED', 'ROLE_ASSIGNED', 'ROLE_REVOKED', 'IMPORTED',
    ];

    public function up(): void
    {
        Schema::create('lesson_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            // What the lesson goes back to when the review ends without publishing.
            $table->string('previous_status', 16);
            $table->char('content_hash', 64);
            $table->timestamp('submitted_at');
            $table->string('resolution', 16)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->foreignId('lesson_version_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->index(['lesson_id', 'submitted_at']);
        });

        DB::statement("ALTER TABLE lesson_reviews ADD CONSTRAINT lesson_reviews_resolution_check CHECK (resolution IN ('PUBLISHED', 'RETURNED', 'WITHDRAWN'))");
        DB::statement("ALTER TABLE lesson_reviews ADD CONSTRAINT lesson_reviews_previous_status_check CHECK (previous_status IN ('DRAFT', 'PUBLISHED'))");
        // A returned review always says why.
        DB::statement("ALTER TABLE lesson_reviews ADD CONSTRAINT lesson_reviews_comment_check CHECK (resolution IS DISTINCT FROM 'RETURNED' OR comment IS NOT NULL)");
        DB::statement('CREATE UNIQUE INDEX lesson_reviews_one_open ON lesson_reviews (lesson_id) WHERE resolution IS NULL');

        $this->actions([...self::ACTIONS, 'RETURNED']);
    }

    public function down(): void
    {
        DB::table('audit_logs')->where('action', 'RETURNED')->update(['action' => 'UPDATED']);
        $this->actions(self::ACTIONS);
        Schema::dropIfExists('lesson_reviews');
    }

    /**
     * @param  list<string>  $actions
     */
    private function actions(array $actions): void
    {
        $list = implode(', ', array_map(fn (string $action) => "'{$action}'", $actions));

        DB::statement('ALTER TABLE audit_logs DROP CONSTRAINT audit_logs_action_check');
        DB::statement("ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_action_check CHECK (action IN ({$list}))");
    }
};
