<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;

class ResetDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (App::environment('demo')) {
            Artisan::call('migrate:fresh --seed');
            $this->components->success('Database has been reset.');
        } else {
            $this->components->error('This command can only be run in the demo environment.');
        }

        return 0;
    }
}
