<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadmaps', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 200);
            $table->text('summary');
            $table->jsonb('description')->nullable();
            $table->string('locale', 5)->default('es');
            $table->enum('unlock_policy', ['ADVISORY', 'STRICT'])->default('ADVISORY');
            $table->smallInteger('mastery_threshold')->default(90);
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE roadmaps ADD CONSTRAINT roadmaps_mastery_threshold_range CHECK (mastery_threshold BETWEEN 50 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmaps');
    }
};
