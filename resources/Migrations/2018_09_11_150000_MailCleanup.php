<?php

use Topoff\LaravelUserLogger\Support\Migration;

class MailCleanup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection($this->connection)->update("UPDATE referers SET source = 'autologin' WHERE medium = 'mail'");
        DB::connection($this->connection)->update("UPDATE referers SET medium = 'email' WHERE medium = 'mail'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
