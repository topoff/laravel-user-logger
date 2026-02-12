<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class LogsAddComment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('logs', 'comment')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table): void {
                $table->string('comment', 50)->nullable()->after('entity_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('logs', 'comment')) {
            Schema::connection($this->connection)->table('logs', function (Blueprint $table): void {
                $table->dropColumn('comment');
            });
        }
    }
}
