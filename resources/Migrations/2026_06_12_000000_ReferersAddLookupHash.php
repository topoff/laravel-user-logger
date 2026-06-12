<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

/**
 * Adds a unique lookup hash so Referer::firstOrCreate() is race-safe
 * (the lookup columns themselves cannot carry a unique index because
 * `url` is a text column).
 *
 * Existing rows keep a NULL hash (multiple NULLs are allowed in a unique
 * index) - they are intentionally not backfilled, because historical
 * duplicates would violate the unique constraint. New lookups create one
 * fresh row per combination and deduplicate from then on.
 */
class ReferersAddLookupHash extends Migration
{
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('referers')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('referers', 'lookup_hash')) {
            return;
        }

        Schema::connection($this->connection)->table('referers', function (Blueprint $table): void {
            $table->string('lookup_hash', 40)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('referers', 'lookup_hash')) {
            return;
        }

        Schema::connection($this->connection)->table('referers', function (Blueprint $table): void {
            $table->dropUnique(['lookup_hash']);
            $table->dropColumn('lookup_hash');
        });
    }
}
