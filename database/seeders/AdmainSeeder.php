<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdmainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            User::create([
            'FirstName' => 'System',
            'LastName' => 'Admin',
            'mobail' => '0999999999',
            'password' => Hash::make('123456789'),
            'ProfileImage' => null,
            'BirthDate' => '1990-01-01',
            'CardImage' => null,
            'is_approved' => 1,
            'user_type' => 'admin'
        ]);
        User::create([
            'FirstName' => 'Bashar',
            'LastName' => 'Al_Shayyah',
            'mobail' => '0969227248',
            'password' => Hash::make('1122334455'),
            'ProfileImage' => null,
            'BirthDate' => '1990-01-01',
            'CardImage' => null,
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
        User::create([
            'FirstName' => 'Ali',
            'LastName' => 'Ali',
            'mobail' => '0999459222',
            'password' => Hash::make('1122334455'),
            'ProfileImage' => null,
            'BirthDate' => '1990-01-01',
            'CardImage' => null,
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
    }
}
