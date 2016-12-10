<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::create('process_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 300)->nullable();
            $table->text('description')->nullable();
            $table->integer('numprocs')->nullable();
            $table->string('path_config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_configs');
    }
}
