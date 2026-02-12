<?php

use Topoff\LaravelUserLogger\Support\Migration;

class MailCleanup extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection($this->connection)->update("UPDATE referers SET source = 'autologin' WHERE medium = 'mail'");
        DB::connection($this->connection)->update("UPDATE referers SET medium = 'email' WHERE medium = 'mail'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
}
