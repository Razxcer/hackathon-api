<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\HazardPoint;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class
        ]);

        $admin = User::create(['name' => 'Админ', 'email' => 'admin@test.ru', 'password' => '123456', 'role' => 'admin']);
        $moder = User::create(['name' => 'Модератор', 'email' => 'moder@test.ru', 'password' => '123456', 'role' => 'moderator']);
        $worker = User::create(['name' => 'Составитель Иван', 'email' => 'worker@test.ru', 'password' => '123456', 'role' => 'worker']);
        User::create(['name' => 'Гость', 'email' => 'guest@test.ru', 'password' => '123456', 'role' => 'guest']);

        // Тестовая одобренная точка (например, ж/д пути в Барнауле)
        HazardPoint::create([
            'title' => 'Выступающая бетонная рампа (путь №4)',
            'description' => 'Негабарит справа по ходу движения, спуститься с подножки заранее!',
            'latitude' => 53.356100,
            'longitude' => 83.749200,
            'danger_radius' => 150,
            'status' => 'approved',
            'created_by' => $worker->id,
            'moderated_by' => $moder->id,
        ]);

        // Тестовая заявка на модерацию
        HazardPoint::create([
            'title' => 'Низко висящий кабель над путями',
            'description' => 'Провис провода около депо',
            'latitude' => 53.361000,
            'longitude' => 83.755000,
            'danger_radius' => 100,
            'status' => 'pending',
            'created_by' => $worker->id,
        ]);
    }
}
