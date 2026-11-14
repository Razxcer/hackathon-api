<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                Task::create([
                    'user_id' => $user->id,
                    'title' => fake('ru_RU')->sentence(3),
                    'description' => fake('ru_RU')->text(100),
                    'status' => fake()->randomElement(['new', 'in_progress', 'done']),
                ]);
            }
        }
    }
}
