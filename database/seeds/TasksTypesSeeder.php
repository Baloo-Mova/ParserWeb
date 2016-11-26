<?php

use Illuminate\Database\Seeder;
use App\Models\TasksType;

class TasksTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tasks_type')->truncate();

        TasksType::insert([
            [
                'name' => 'Поиск по ключевому слову'
            ],
            [
                'name' => 'Поиск по списку сайтов'
            ]
        ]);
    }
}
