<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The video catalog (§32, ADR-035): a video of a provider, by its ID, with
 * the metadata oEmbed gives (title, channel, thumbnail) and what the
 * editor adds (description, duration, level). Learners only see videos
 * whose last oEmbed check was OK. Attached like resources (video_links).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 16);
            $table->string('external_id', 64);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->string('instructor', 160)->nullable();
            $table->enum('difficulty', ['BEGINNER', 'INTERMEDIATE', 'ADVANCED', 'EXPERT'])->nullable();
            $table->string('language', 5)->default('en');
            $table->enum('link_status', ['UNCHECKED', 'OK', 'REDIRECTED', 'BROKEN'])->default('UNCHECKED');
            $table->timestamp('last_checked_at')->nullable();
            $table->smallInteger('last_http_status')->nullable();
            $table->enum('status', ['DRAFT', 'REVIEW', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index('link_status');
        });

        DB::statement("ALTER TABLE videos ADD CONSTRAINT videos_provider_check CHECK (provider IN ('YOUTUBE'))");
        DB::statement("ALTER TABLE videos ADD CONSTRAINT videos_youtube_id_check CHECK (provider <> 'YOUTUBE' OR external_id ~ '^[A-Za-z0-9_-]{11}$')");
        DB::statement('ALTER TABLE videos ADD CONSTRAINT videos_duration_check CHECK (duration_seconds IS NULL OR duration_seconds > 0)');

        Schema::create('video_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->string('linkable_type', 32);
            $table->unsignedBigInteger('linkable_id');
            $table->smallInteger('position')->default(0);
            $table->string('note', 500)->nullable();
            // Where to start playing, for a long video of which one part matters.
            $table->unsignedInteger('start_seconds')->nullable();

            $table->unique(['video_id', 'linkable_type', 'linkable_id']);
            $table->index(['linkable_type', 'linkable_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_links');
        Schema::dropIfExists('videos');
    }
};
