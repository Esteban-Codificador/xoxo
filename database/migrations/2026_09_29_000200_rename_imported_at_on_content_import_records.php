<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * content:export also marks a record as in sync with the package (ADR-031),
 * so the timestamp is the last sync, not only the last import.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_import_records', function (Blueprint $table) {
            $table->renameColumn('imported_at', 'synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('content_import_records', function (Blueprint $table) {
            $table->renameColumn('synced_at', 'imported_at');
        });
    }
};
