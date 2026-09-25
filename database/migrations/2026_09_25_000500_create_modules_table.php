<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained()->restrictOnDelete();
            $table->string('slug', 120);
            $table->string('title', 200);
            $table->text('summary');
            $table->smallInteger('position')->default(0);
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['track_id', 'slug']);
            $table->index(['track_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
