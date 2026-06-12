<?php

declare(strict_types=1);

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
use Topoff\LaravelUserLogger\Support\IpHasher;

class HashIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:haship';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hashes an ip to the value saved in the database.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $clientIp = $this->ask('Which ip?');

        $this->line('This is the hashed value: '.IpHasher::hash((string) $clientIp));
    }
}
