<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Maps content package keys to database rows so imports are idempotent
        // and never overwrite what editors changed in the CMS.
        Schema::create('content_import_records', function (Blueprint $table) {
            $table->id();
            $table->string('package', 64);
            $table->string('key', 191);
            $table->string('importable_type', 32);
            $table->unsignedBigInteger('importable_id');
            $table->char('source_hash', 64);
            $table->char('entity_hash', 64);
            $table->timestamp('imported_at');

            $table->unique(['package', 'key']);
            $table->index(['importable_type', 'importable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_import_records');
    }
};
