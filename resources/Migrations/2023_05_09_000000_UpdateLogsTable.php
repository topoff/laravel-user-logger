<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class UpdateLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('logs') && Schema::connection($this->connection)->hasColumn('logs', 'uri_id')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table): void {
                $table->bigInteger('uri_id')->unsigned()->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('logs') && Schema::connection($this->connection)->hasColumn('logs', 'uri_id')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table): void {
                $table->bigInteger('uri_id')->unsigned()->change();
            });
        }
    }
}
