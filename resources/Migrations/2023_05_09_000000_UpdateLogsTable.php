<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class UpdateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection($this->connection)->hasTable('logs')) {
            if (Schema::connection($this->connection)->hasColumn('logs', 'uri_id')) {
                Schema::connection($this->connection)->table('logs', function (Blueprint $table) {
                    $table->bigInteger('uri_id')->unsigned()->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::connection($this->connection)->hasTable('logs')) {
            if (Schema::connection($this->connection)->hasColumn('logs', 'uri_id')) {
                Schema::connection($this->connection)->table('logs', function (Blueprint $table) {
                    $table->bigInteger('uri_id')->unsigned()->change();
                });
            }
        }
    }
}
