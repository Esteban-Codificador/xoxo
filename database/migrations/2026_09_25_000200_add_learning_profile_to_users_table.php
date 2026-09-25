<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->string('locale', 5)->default('es');
            $table->string('timezone', 64)->default('UTC');
            $table->text('bio')->nullable();
            $table->string('avatar_path')->nullable();
            $table->enum('profile_visibility', ['PRIVATE', 'PUBLIC'])->default('PRIVATE');
            $table->integer('xp_total')->default(0);
            $table->timestamp('last_active_at')->nullable();
        });

        // Usernames are public URLs (/u/{username}): lowercase, 3-40 chars, no leading/trailing hyphen.
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_username_format CHECK (username ~ '^[a-z0-9](?:[a-z0-9-]{1,38})[a-z0-9]$')");
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_xp_total_non_negative CHECK (xp_total >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_username_format');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_xp_total_non_negative');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'locale', 'timezone', 'bio', 'avatar_path', 'profile_visibility', 'xp_total', 'last_active_at']);
        });
    }
};
