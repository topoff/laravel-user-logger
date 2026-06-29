<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

/**
 * Indexes sessions.client_ip. Without it, the suspicious-session lookups
 * (`... where client_ip = ?`, e.g. the count(*) join over logs and the
 * `update sessions set is_suspicious = ? where client_ip = ?`) full-scan the
 * sessions table — observed as multi-second slow queries in production.
 *
 * client_ip is varchar(45) (see SessionsWidenClientIp), so a full-column
 * index is within InnoDB key-length limits. On MySQL 8 the ALTER is ONLINE
 * (INPLACE), so concurrent reads/writes keep working while it builds.
 */
class SessionsAddClientIpIndex extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('sessions') || ! $schema->hasColumn('sessions', 'client_ip')) {
            return;
        }

        if ($schema->hasIndex('sessions', ['client_ip'])) {
            return;
        }

        $schema->table('sessions', function (Blueprint $table): void {
            $table->index('client_ip');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('sessions', 'client_ip')) {
            return;
        }

        if (! $schema->hasIndex('sessions', ['client_ip'])) {
            return;
        }

        $schema->table('sessions', function (Blueprint $table): void {
            $table->dropIndex(['client_ip']);
        });
    }
}
