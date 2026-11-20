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
        $worker = User::create(['name' => 'Кирилл', 'email' => 'arbuzik31.31@gmail.com', 'password' => '123456', 'role' => 'worker']);
        $worker = User::create(['name' => 'Иван', 'email' => 'sysolinivan753@gmail.com', 'password' => '123456', 'role' => 'worker']);
        $worker = User::create(['name' => 'Вадим', 'email' => 'root@gmail.com', 'password' => '123456', 'role' => 'moderator']);
        User::create(['name' => 'Гость', 'email' => 'guest@test.ru', 'password' => '123456', 'role' => 'guest']);

        // Тестовая одобренная точка (например, ж/д пути в Барнауле)
        // HazardPoint::create([
        //     'title' => 'Выступающая бетонная рампа (путь №4)',
        //     'description' => 'Негабарит справа по ходу движения, спуститься с подножки заранее!',
        //     'latitude' => 53.356100,
        //     'longitude' => 83.749200,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);

        // // Тестовая заявка на модерацию
        // HazardPoint::create([
        //     'title' => 'Низко висящий кабель над путями',
        //     'description' => 'Провис провода около депо',
        //     'latitude' => 53.361000,
        //     'longitude' => 83.755000,
        //     'danger_radius' => 500,
        //     'status' => 'pending',
        //     'created_by' => $worker->id,
        // ]);

        // //Хорошая точка
        // HazardPoint::create([
        //     'title' => 'Сугроб на путях',
        //     'description' => 'Снежый занос перекрывает путь',
        //     'latitude' => 53.334044,
        //     'longitude' => 83.696042,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);

        // 53.334102, 83.695286

        // //Тест в технаре далеко по коридору
        // HazardPoint::create([
        //     'title' => 'Посторонний предмет',
        //     'description' => 'Предмет на рельсах',
        //     'latitude' => 53.334102,
        //     'longitude' => 83.695286,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);

        // 53.333403735056045, 83.69624472864318

        //Тест в технаре БЛИЗКО по коридору
        HazardPoint::create([
            'title' => 'Посторонний предмет',
            'description' => 'Предмет на рельсах',
            'latitude' => 53.333403735056045,
            'longitude' => 83.69624472864318,
            'danger_radius' => 500,
            'status' => 'approved',
            'created_by' => $worker->id,
            'moderated_by' => $moder->id,
        ]);

        //Тест на улице возле технаря
        HazardPoint::create([
            'title' => 'Дерево на путях',
            'description' => 'Дерево упавшее на пути',
            'latitude' => 53.334102,
            'longitude' => 83.695286,
            'danger_radius' => 500,
            'status' => 'approved',
            'created_by' => $worker->id,
            'moderated_by' => $moder->id,
        ]);

        //Тест возле вадима 53.37309190412699, 83.71462043611632
        HazardPoint::create([
            'title' => 'Сугроб на путях',
            'description' => 'Много снега возле путей',
            'latitude' => 53.37309190412699,
            'longitude' => 83.71462043611632,
            'danger_radius' => 500,
            'status' => 'approved',
            'created_by' => $worker->id,
            'moderated_by' => $moder->id,
        ]);

        // HazardPoint::create([
        //     'title' => 'Упавшее дерево',
        //     'description' => 'Дерево упало после ветра',
        //     'latitude' => 53.335411,
        //     'longitude' => 83.692851,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);

        // HazardPoint::create([
        //     'title' => 'Посторонний предмет',
        //     'description' => 'Предмет на рельсах',
        //     'latitude' => 53.333505,
        //     'longitude' => 83.698299,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);

        // HazardPoint::create([
        //     'title' => 'Оползень',
        //     'description' => 'Грунт сошёл на путь',
        //     'latitude' => 53.343033,
        //     'longitude' => 83.711998,
        //     'danger_radius' => 500,
        //     'status' => 'approved',
        //     'created_by' => $worker->id,
        //     'moderated_by' => $moder->id,
        // ]);
    }
}
