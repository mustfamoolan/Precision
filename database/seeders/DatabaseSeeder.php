<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bank;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Admin User
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('12345678'),
                'role' => 'admin',
            ]
        );

        // Default Bank Accounts
        $banks = ['Bank 1', 'Bank 2', 'Cash'];
        foreach ($banks as $bankName) {
            Bank::firstOrCreate(
                ['name' => $bankName],
                ['balance' => 0]
            );
        }
    }
}
