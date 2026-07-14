<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'prince.sestoso@gmail.com'],
            [
                'password'    => Hash::make('password'),
                'provider' => 'local'
            ]
        );

        $initials = strtoupper(
            substr('Prince', 0, 1) . substr('Sestoso', 0, 1)
        );

        PlatformAdmin::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'first_name' => 'Prince',
                'last_name' => 'Sestoso',
                'avatar' => 'https://ui-avatars.com/api/?name=' . $initials
            ]
        );
    }
}
