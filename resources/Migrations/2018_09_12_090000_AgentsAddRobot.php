<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class AgentsAddRobot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::connection($this->connection)->hasColumn('agents', 'is_robot')){
            Schema::connection($this->connection)->table('agents', function (Blueprint $table) {
                $table->boolean('is_robot')->default(false)->after('browser_version');
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
        if(!Schema::connection($this->connection)->hasColumn('agents', 'is_robot')){
            Schema::connection($this->connection)->table('agents', function (Blueprint $table) {
                $table->dropColumn('is_robot');
            });
        }
    }
}
