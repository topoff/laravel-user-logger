<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateULSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::connection($this->connection)->hasTable('sessions')) {
            Schema::connection($this->connection)->create('sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->integer('user_id')->unsigned()->nullable();
                $table->bigInteger('device_id')->unsigned()->nullable();
                $table->bigInteger('agent_id')->unsigned()->nullable();
                $table->bigInteger('referer_id')->unsigned()->nullable();
                $table->bigInteger('language_id')->unsigned()->nullable();
                $table->string('client_ip', 32)->nullable();
                $table->boolean('is_robot')->nullable();

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

                $table->foreign('device_id')->references('id')->on('devices');
                $table->foreign('agent_id')->references('id')->on('agents');
                $table->foreign('referer_id')->references('id')->on('referers');
                $table->foreign('language_id')->references('id')->on('languages');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('sessions');
    }
}
