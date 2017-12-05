<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\Tracker\Support\Migration;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('agents', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->text('name');
            $table->string('name_hash', 48)->index();
            $table->string('browser', 255);
            $table->string('browser_version', 255);

            $table->unique(['name_hash']);

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
        Schema::connection($this->connection)->dropIfExists('agents');
    }
}
