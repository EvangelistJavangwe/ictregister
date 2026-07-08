<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default Super Admin
        User::create([
            'username'        => 'GMB-EMP-0001',
            'firstname'       => 'System',
            'lastname'        => 'Administrator',
            'employee_number' => 'ADMIN001',
            'email'           => 'admin@ictregister.local',
            'phone'           => '+263771000000',
            'role'            => 'super_admin',
            'designation'     => null,
            'is_blocked'      => false,
            'mfa_enabled'     => false,
            'must_change_password' => false,
            'password'        => Hash::make('Admin@1234'),
        ]);

        $this->command->info('✓ Super Admin created:');
        $this->command->info('  Username: GMB-EMP-0001');
        $this->command->info('  Password: Admin@1234');
        $this->command->warn('  ⚠ Change the password immediately after first login!');
    }
}
