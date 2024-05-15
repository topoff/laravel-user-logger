<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class LogsAddComment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::connection($this->connection)->hasColumn('logs', 'comment')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table) {
                $table->string('comment', 50)->nullable()->after('entity_id');
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
        if (! Schema::connection($this->connection)->hasColumn('logs', 'comment')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table) {
                $table->dropColumn('comment');
            });
        }
    }
}
