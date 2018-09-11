<?php

use Topoff\LaravelUserLogger\Support\Migration;

class CleanupReferers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->update("UPDATE touserlog.referers SET medium = null, source = null WHERE medium = '' AND source = ''");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection($this->connection)->update("UPDATE touserlog.referers SET medium = '', source = '' WHERE medium IS NULL AND source IS NULL");
    }
}
