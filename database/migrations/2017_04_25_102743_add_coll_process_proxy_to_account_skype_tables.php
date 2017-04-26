<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollProcessProxyToAccountSkypeTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up() {
        Schema::table('accounts_data', function ($table) {
            $table->integer('is_sender')->default(0)->after('valid');
            $table->integer('process_id')->default(0)->after('valid');
            $table->integer('proxy_id')->default(0)->after('valid');
    
        });
        Schema::table('skype_logins', function ($table) {
            $table->integer('process_id')->default(0)->after('valid');
            $table->integer('proxy_id')->default(0)->after('valid');
    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
     
         Schema::table('accounts_data', function ($table) {
            $table->dropColumn('process_id');
            $table->dropColumn('proxy_id');
            $table->dropColumn('is_sender');
    
        });
        Schema::table('skype_logins', function ($table) {
            $table->dropColumn('process_id');
            $table->dropColumn('proxy_id');
    
        });
        
    }
}
