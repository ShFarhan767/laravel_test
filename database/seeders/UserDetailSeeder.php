<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class UserDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        foreach (range(1, 50) as $index) {
            DB::table('user_details')->insert([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'country' => $faker->country,
                'registration_date' => $faker->dateTimeBetween('2025-01-01', '2025-12-31')->format('Y-m-d'),
                'status' => $faker->randomElement(['verified', 'unverified']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
