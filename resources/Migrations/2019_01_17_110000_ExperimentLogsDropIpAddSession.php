<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class ExperimentLogsDropIpAddSession extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('experimentlogs', 'client_ip')) {
            DB::connection($this->connection)->delete('DELETE FROM experimentlogs');

            Schema::connection($this->connection)->table('experimentlogs', function (Blueprint $table): void {
                $table->dropColumn('client_ip');
                $table->char('session_id', 36)->index()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('experimentlogs', 'client_ip')) {
            DB::connection($this->connection)->delete('DELETE FROM experimentlogs');

            Schema::connection($this->connection)->table('experimentlogs', function (Blueprint $table): void {
                $table->string('client_ip', 32)->index()->after('id');
                $table->dropColumn('session_id');
            });
        }
    }
}
