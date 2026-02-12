<?php

namespace Topoff\LaravelUserLogger\Console\Commands;

use Illuminate\Console\Command;
use Topoff\LaravelUserLogger\Models\ExperimentLog;

class Flush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-logger:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes the experiments logs to start a new experiment.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ExperimentLog::truncate();
    }
}
