<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the first platform admin. Password comes from ADMIN_SEED_PASSWORD in
     * the environment (never hard-coded — the OV500 portal shipped admin/admin
     * and the vendor's own live credentials in the repo). Idempotent.
     */
    public function run(): void
    {
        $email = env('ADMIN_SEED_EMAIL', 'admin@example.com');
        $password = env('ADMIN_SEED_PASSWORD');
        $name = env('ADMIN_SEED_NAME', 'Administrator');

        if (blank($password)) {
            $this->command->warn('ADMIN_SEED_PASSWORD not set — skipping admin seed.');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'       => $name,
                'password'   => Hash::make($password),
                'role'       => 'admin',
                'account_id' => null, // platform-wide
            ],
        );

        $this->command->info("Admin user ensured: {$email}");
    }
}
