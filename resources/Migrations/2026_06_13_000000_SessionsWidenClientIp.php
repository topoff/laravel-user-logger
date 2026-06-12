<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Topoff\LaravelUserLogger\Support\Migration;

/**
 * Widens client_ip from 32 (hash length) to 45 chars so plain-text ips
 * fit as well (IPv6 needs up to 45 chars, e.g. IPv4-mapped addresses).
 */
class SessionsWidenClientIp extends Migration
{
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('sessions', 'client_ip')) {
            return;
        }

        Schema::connection($this->connection)->table('sessions', function (Blueprint $table): void {
            $table->string('client_ip', 45)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('sessions', 'client_ip')) {
            return;
        }

        Schema::connection($this->connection)->table('sessions', function (Blueprint $table): void {
            $table->string('client_ip', 32)->nullable()->change();
        });
    }
}
