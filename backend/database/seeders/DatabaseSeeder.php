<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyContact;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $properties = [
            [
                'name' => 'Sunset Apartments',
                'address' => '123 Sunset Boulevard, Los Angeles, CA 90001',
                'contact' => ['Manager Office', '555-0100', 'sunset@example.com']
            ],
            [
                'name' => 'Harbor View Complex',
                'address' => '456 Harbor Drive, San Diego, CA 92101',
                'contact' => ['Front Desk', '555-0200', 'harbor@example.com']
            ],
            [
                'name' => 'Mountain Ridge',
                'address' => '789 Mountain Road, Denver, CO 80201',
                'contact' => ['Admin Office', '555-0300', 'mountain@example.com']
            ],
        ];

        foreach ($properties as $propertyData) {
            $property = Property::create([
                'name' => $propertyData['name'],
                'address' => $propertyData['address'],
            ]);

            PropertyContact::create([
                'property_id' => $property->id,
                'name' => $propertyData['contact'][0],
                'phone' => $propertyData['contact'][1],
                'email' => $propertyData['contact'][2],
                'position' => 0,
            ]);
        }
    }
}
