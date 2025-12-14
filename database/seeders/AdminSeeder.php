<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            User::create([
            'FirstName' => 'System',
            'LastName' => 'Admin',
            'mobile' => '0999999999',
            'password' => Hash::make('123456789'),
<<<<<<< HEAD
            'ProfileImage' => 'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' => 'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 1,
            'user_type' => 'admin'
        ]);
        User::create([
            'FirstName' => 'Bashar',
            'LastName' => 'Al_Shayyah',
            'mobile' => '0969227248',
            'password' => Hash::make('1122334455'),
<<<<<<< HEAD
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' => 'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' =>['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
        User::create([
            'FirstName' => 'Ali',
            'LastName' => 'Ali',
            'mobile' => '0999459222',
            'password' => Hash::make('1122334455'),
<<<<<<< HEAD
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' =>  'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
          User::create([
            'FirstName' => 'Ali',
            'LastName' => 'Ali',
            'mobile' => '0969227244',
            'password' => Hash::make('1122334455'),
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
<<<<<<< HEAD
            'CardImage' => 'default.jpg',
=======
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 1,
            'user_type' => 'user'
        ]);
               User::create([
            'FirstName' => 'ammar',
            'LastName' => 'Al_Shayyah',
            'mobile' => '0981491111',
            'password' => Hash::make('1122334455'),
<<<<<<< HEAD
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' => 'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
           User::create([
            'FirstName' => 'lina',
            'LastName' => 'Al_Shayyah',
            'mobile' => '0988888888',
            'password' => Hash::make('1122334455'),
<<<<<<< HEAD
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' =>  'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
           User::create([
            'FirstName' => 'rana',
            'LastName' => 'Al_Shayyah',
            'mobile' => '0969217248',
            'password' => Hash::make('1122334455'),
<<<<<<< HEAD
            'ProfileImage' =>  'default.jpg',
            'BirthDate' => '1990-01-01',
            'CardImage' =>  'default.jpg',
=======
            'ProfileImage' => ['default.jpg'],
            'BirthDate' => '1990-01-01',
            'CardImage' => ['default.jpg'],
>>>>>>> 2f599a976a2922343fc5c36cbef0ddc06f90f130
            'is_approved' => 0,
            'user_type' => 'user'
        ]);
    }
}
