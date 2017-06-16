<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMessengersInContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('reserved_viber')->after('reserved')->default(0);
            $table->boolean('reserved_whatsapp')->after('reserved_viber')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function(Blueprint $table){
            $table->dropColumn('reserved_viber');
            $table->dropColumn('reserved_whatsapp');
        });
    }
}
