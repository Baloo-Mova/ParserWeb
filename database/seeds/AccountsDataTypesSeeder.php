<?php

use Illuminate\Database\Seeder;

use App\Models\AccountsDataTypes;

class AccountsDataTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('accounts_data_types')->truncate();

        AccountsDataTypes::insert([
            [
                'type_name' => 'VK'
            ],
            [
                'type_name' => 'OK'
            ],
            [
                'type_name' => 'SMTP'
            ]
        ]);
    }
}
