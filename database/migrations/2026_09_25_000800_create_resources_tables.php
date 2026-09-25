<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('url', 2048)->unique();
            $table->enum('type', ['DOCUMENTATION', 'ARTICLE', 'TUTORIAL', 'COURSE', 'BOOK', 'PAPER', 'REPOSITORY', 'TOOL']);
            $table->string('provider', 120);
            $table->text('description');
            $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])->nullable();
            $table->string('language', 5)->default('en');
            $table->boolean('is_official')->default(false);
            $table->enum('link_status', ['UNCHECKED', 'OK', 'REDIRECTED', 'BROKEN'])->default('UNCHECKED');
            $table->timestamp('last_checked_at')->nullable();
            $table->smallInteger('last_http_status')->nullable();
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('link_status');
        });

        // Polymorphic attachment of a resource to lessons, skills, tracks or projects.
        Schema::create('resource_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('linkable_type', 32);
            $table->unsignedBigInteger('linkable_id');
            $table->smallInteger('position')->default(0);
            $table->string('note', 500)->nullable();

            $table->unique(['resource_id', 'linkable_type', 'linkable_id']);
            $table->index(['linkable_type', 'linkable_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_links');
        Schema::dropIfExists('resources');
    }
};
