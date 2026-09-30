<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Files used by the content (§55, ADR-034). Content-addressed: the path
 * and the unique checksum come from the stored bytes, so the same image
 * uploaded twice, or imported after being exported, is one row. The
 * alternative text is not here: it belongs to each place the image is
 * used, in the RichContent node.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('disk', 32);
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('checksum', 64)->unique();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE media_assets ADD CONSTRAINT media_assets_kind_check CHECK (kind IN ('IMAGE', 'AUDIO', 'VIDEO', 'FILE'))");
        DB::statement('ALTER TABLE media_assets ADD CONSTRAINT media_assets_size_check CHECK (size_bytes > 0)');
        // An image always knows its size: the reader reserves its box before it loads.
        DB::statement("ALTER TABLE media_assets ADD CONSTRAINT media_assets_dimensions_check CHECK (kind <> 'IMAGE' OR (width > 0 AND height > 0))");
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
