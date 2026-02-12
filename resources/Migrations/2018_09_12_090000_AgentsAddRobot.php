<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class AgentsAddRobot extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('agents', 'is_robot')) {
            Schema::connection($this->connection)->table('agents', function (Blueprint $table): void {
                $table->boolean('is_robot')->default(false)->after('browser_version');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('agents', 'is_robot')) {
            Schema::connection($this->connection)->table('agents', function (Blueprint $table): void {
                $table->dropColumn('is_robot');
            });
        }
    }
}
