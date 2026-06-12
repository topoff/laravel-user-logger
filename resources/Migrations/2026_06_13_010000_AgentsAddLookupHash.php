<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

/**
 * Adds a unique lookup hash so Agent::firstOrCreate() is race-safe
 * (`name` is a text column and cannot carry a full unique index).
 *
 * Existing rows keep a NULL hash (multiple NULLs are allowed in a unique
 * index) - they are intentionally not backfilled, because historical
 * duplicates would violate the unique constraint.
 */
class AgentsAddLookupHash extends Migration
{
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('agents')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('agents', 'lookup_hash')) {
            return;
        }

        Schema::connection($this->connection)->table('agents', function (Blueprint $table): void {
            $table->string('lookup_hash', 40)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('agents', 'lookup_hash')) {
            return;
        }

        Schema::connection($this->connection)->table('agents', function (Blueprint $table): void {
            $table->dropUnique(['lookup_hash']);
            $table->dropColumn('lookup_hash');
        });
    }
}
