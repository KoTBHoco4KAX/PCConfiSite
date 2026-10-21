<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'user'  => 'Пользователь',
            'admin' => 'Администратор',
        ];

        foreach ($roles as $slug => $name) {
            Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $userRole  = Role::where('slug', 'user')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $demoUsers = [
            [
                'name'     => 'Демо Пользователь',
                'email'    => 'user@pc.local',
                'password' => 'password',
                'roles'    => [$userRole->id],
            ],
            [
                'name'     => 'Демо Админ',
                'email'    => 'admin@pc.local',
                'password' => 'password',
                'roles'    => [$adminRole->id],
            ],
            // Совместимость со старым демо-логином
            [
                'name'     => 'Демо Customer',
                'email'    => 'customer@pc.local',
                'password' => 'password',
                'roles'    => [$userRole->id],
            ],
        ];

        foreach ($demoUsers as $data) {
                $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(), // <--- ВОТ ЭТА СТРОКА
                ]
            );
            $user->roles()->syncWithoutDetaching($data['roles']);
        }
    }
}