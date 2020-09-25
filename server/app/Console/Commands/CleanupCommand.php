<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
     * Directories to be removed.
     *
     * @var array
     */
    protected $directoriesToRemove = [
        'app/Events',
        'app/Jobs',
        'app/Listeners',
        'database/seeds',
    ];

    /**
     * Files to be removed.
     *
     * @var array
     */
    protected $filesToRemove = [
        'app/Console/Commands/.gitkeep',
        'app/Http/Controllers/ExampleController.php',
        'app/Http/Middleware/Cors.php',
        'app/Http/Middleware/ExampleMiddleware.php',
        'app/Http/Middleware/MailConfig.php',
        'database/factories/ModelFactory.php',
        'database/migrations/.gitkeep',
        'resources/views/.gitkeep',
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
        foreach ($this->directoriesToRemove as $path) {
            if (!File::exists($path)) {
                continue;
            }

            $result = File::deleteDirectory($path);

            if (!$result) {
                Log::warning('Could not delete unnecessary path on cleanup: ' . $path);
            }
        }

        foreach ($this->filesToRemove as $path) {
            if (!File::exists($path)) {
                continue;
            }

            $result = File::delete($path);

            if (!$result) {
                Log::warning('Could not delete unnecessary path on cleanup: ' . $path);
            }
        }
    }
}
