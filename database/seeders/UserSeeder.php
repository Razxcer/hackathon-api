<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Тестовый аккаунт для быстрой проверки на демо
        User::create([
            'name' => 'Тестовый Юзер',
            'email' => 'test@mail.ru',
            'password' => Hash::make('123456'),
        ]);

        // Еще 3 случайных пользователя
        for ($i = 0; $i < 3; $i++) {
            User::create([
                'name' => fake('ru_RU')->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('123456'),
            ]);
        }
    }
}
