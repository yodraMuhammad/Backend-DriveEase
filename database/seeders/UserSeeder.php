<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Menambahkan dua data pengguna
        for ($i = 0; $i < 2; $i++) {
            User::create([
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'license_number' => $faker->unique()->word, // Ganti dengan format yang sesuai jika perlu
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password123'), // Password yang di-hash
            ]);
        }
    }
}