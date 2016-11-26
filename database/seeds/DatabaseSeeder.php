<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AdminUser::class);
        $this->call(AccountsDataTypesSeeder::class);
        $this->call(TasksTypesSeeder::class);
    }
}
