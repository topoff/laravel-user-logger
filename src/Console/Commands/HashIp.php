<?php

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $clientIp = $this->ask('Which ip?');

        $clientIp = md5($clientIp);
        $this->line('This is the hashed value: '.substr($clientIp, 0, 10).substr($clientIp, 20, 12).substr($clientIp, 11, 10));
    }
}
