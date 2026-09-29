<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skills and resources become editable in the CMS (phase 5b): like tracks
 * and lessons they record who created and last changed them, so
 * instructors can edit what they created (roadmap §4).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['skills', 'resources'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['skills', 'resources'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by');
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
