<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create('logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('session_id')->unsigned()->nullable()->index();
            $table->bigInteger('uri_id')->unsigned()->index();
            $table->string('event', 50)->nullable()->index();

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('session_id')->references('id')->on('sessions');
            $table->foreign('uri_id')->references('id')->on('uris');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists('logs');
    }
}
