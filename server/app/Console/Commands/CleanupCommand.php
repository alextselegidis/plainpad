<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removed unnecessary system files.';

    /**
     * Files to be removed.
     *
     * @var array
     */
    protected $toRemove = [
    ];

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
     */
    public function handle()
    {
        foreach ($this->toRemove as $path) {
            if (!Storage::exists($path)) {
                continue;
            }

            $result = Storage::delete($path);

            if (!$result) {
                Log::warning('Could not delete unnecessary path on cleanup: ' . $path);
            }
        }
    }
}
