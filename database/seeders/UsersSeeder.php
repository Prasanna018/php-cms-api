<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Create roles
       $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $authorRole = Role::firstOrCreate(['name' => 'author']);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole($adminRole);

        // Create author user
        $author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => Hash::make('password'),
        ]);
        $author->assignRole($authorRole);

        // Create additional authors
        User::factory()->count(5)->create()->each(function ($user) use ($authorRole) {
            $user->assignRole($authorRole);
        });
    }
}
