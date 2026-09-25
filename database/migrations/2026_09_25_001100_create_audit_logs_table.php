<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: the application never updates or deletes rows.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action', [
                'CREATED', 'UPDATED', 'DELETED', 'SUBMITTED', 'PUBLISHED', 'UNPUBLISHED',
                'ARCHIVED', 'RESTORED', 'ROLE_ASSIGNED', 'ROLE_REVOKED', 'IMPORTED',
            ]);
            $table->string('auditable_type', 32);
            $table->unsignedBigInteger('auditable_id');
            $table->jsonb('changes')->default(DB::raw("'{}'::jsonb"));
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
