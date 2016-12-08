<?php

use Illuminate\Database\Seeder;

class ProcessConfigsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
          TasksType::insert([
            [
                'name' => 'proxy',
               
                'numprocs' => '1'
            ],
            [
                'name' => 'google',
                
                'numprocs' => '2'
            ]
        ]);
}

    }
