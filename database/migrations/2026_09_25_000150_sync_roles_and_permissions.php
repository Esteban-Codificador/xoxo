<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Roles and permissions are system configuration, not demo data: every
 * environment gets them on migrate, so registration (which assigns STUDENT)
 * never depends on someone remembering to run db:seed. The seeder is
 * idempotent; a later migration can call it again after the matrix changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // The permission tables are dropped by the previous migration's rollback.
    }
};
