<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
use Topoff\LaravelUserLogger\Models\Session;

class PruneIps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:prune-ips {--days= : Override retention.ip_days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes stored client ips from sessions older than the configured retention (the sessions themselves are kept).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('user-logger.retention.ip_days', 0));

        if ($days <= 0) {
            $this->warn('Ip pruning is disabled - set user-logger.retention.ip_days or pass --days.');

            return self::FAILURE;
        }

        $count = Session::query()
            ->where('updated_at', '<', now()->subDays($days))
            ->whereNotNull('client_ip')
            ->update(['client_ip' => null]);

        $this->info("Removed the client ip from {$count} session(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
