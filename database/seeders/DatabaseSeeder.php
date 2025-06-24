<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed roles table directly
        $this->seedRoles();

        // Create 10 users
        User::factory(10)->create();

        // Assign roles to users
        $this->assignRolesToUsers();
    }

    /**
     * Seed the roles table.
     *
     * @return void
     */
    private function seedRoles()
    {
        $roles = [
            'entry',
            'exit',
            'admin'
        ];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }

    /**
     * Assign roles to users.
     *
     * @return void
     */
    private function assignRolesToUsers()
    {
        // Get the roles
        $roles = Role::all();

        // Assign roles to users
        User::all()->each(function ($user) use ($roles) {
            // Randomly assign a role from the roles table
            $user->roles()->attach($roles->random()->id);
        });
    }
}
