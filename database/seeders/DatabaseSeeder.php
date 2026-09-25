<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Roles, demo users (never in production) and the initial curriculum.
     * Curriculum text lives in content/, not here: this only runs the importer.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (! app()->isProduction()) {
            $this->seedDemoUsers();
        }

        Artisan::call('content:import', ['path' => 'content/ai-engineer'], $this->command->getOutput());
    }

    private function seedDemoUsers(): void
    {
        foreach (Role::cases() as $role) {
            $slug = strtolower($role->value);

            $user = User::query()->firstOrCreate(
                ['email' => "{$slug}@ai-roadmap.test"],
                ['name' => ucfirst($slug).' Demo', 'username' => "demo-{$slug}", 'password' => Hash::make('password')],
            );

            // email_verified_at is not mass assignable: demo accounts are verified explicitly.
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();
            $user->syncRoles([$role->value]);
        }
    }
}
