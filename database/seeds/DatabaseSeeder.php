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
        $this->call(UserNamesTableSeeder::class);
        $this->call(FUserNamesSeeder::class);
        $this->call(SUserNamesSeeder::class);
        $this->call(ThUserNamesSeeder::class);
        $this->call(FoUserNamesSeeder::class);

    }
}
