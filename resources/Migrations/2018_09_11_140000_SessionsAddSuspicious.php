<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

class SessionsAddSuspicious extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('sessions', 'is_suspicious')) {
            Schema::connection($this->connection)->table('sessions', function (Blueprint $table): void {
                $table->boolean('is_suspicious')->default(false)->after('is_robot');
            });

            DB::connection($this->connection)->update('UPDATE sessions SET is_suspicious = TRUE WHERE is_robot IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('sessions', 'is_suspicious')) {
            Schema::connection($this->connection)->table('sessions', function (Blueprint $table): void {
                $table->dropColumn('is_suspicious');
            });
        }
    }
}
