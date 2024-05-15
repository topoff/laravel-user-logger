<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class CreateExperimentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::connection($this->connection)->hasTable('experimentlogs')) {
            Schema::connection($this->connection)->create('experimentlogs', function (Blueprint $table) {
                $table->smallIncrements('id');

                $table->string('client_ip', 32)->index();
                $table->string('experiment', 20)->index();

                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
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
        Schema::connection($this->connection)->dropIfExists('experimentlogs');
    }
}
