<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Avery Johnson',
                'email' => 'avery.johnson@example.com',
            ],
            [
                'name' => 'Maya Patel',
                'email' => 'maya.patel@example.com',
            ],
            [
                'name' => 'Noah Williams',
                'email' => 'noah.williams@example.com',
            ],
            [
                'name' => 'Sofia Garcia',
                'email' => 'sofia.garcia@example.com',
            ],
            [
                'name' => 'Ethan Chen',
                'email' => 'ethan.chen@example.com',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['email' => $customer['email']],
                $customer
            );
        }
    }
}
