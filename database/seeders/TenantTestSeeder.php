<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantTestSeeder extends Seeder
{
    /**
     * Seed two agencies and one user in each for multi-tenant testing.
     */
    public function run(): void
    {
        // Create two test agencies
        $agencyA = Agency::firstOrCreate(
            ['name' => 'Test Agency A'],
            []
        );

        $agencyB = Agency::firstOrCreate(
            ['name' => 'Test Agency B'],
            []
        );

        // Create a user for Agency A
        User::firstOrCreate(
            ['email' => 'agentA@example.com'],
            [
                'name'      => 'Agent A',
                'password'  => Hash::make('password'), // test password
                'agency_id' => $agencyA->id,
            ]
        );

        // Create a user for Agency B
        User::firstOrCreate(
            ['email' => 'agentB@example.com'],
            [
                'name'      => 'Agent B',
                'password'  => Hash::make('password'), // test password
                'agency_id' => $agencyB->id,
            ]
        );
    }
}
