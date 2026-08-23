<?php

namespace App\Console\Commands;

use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;

/**
 * A thin wrapper around `db:seed --class=...` — exists so a deploy's
 * pre-deploy/release command can be a single, plain command name
 * (`php artisan db:seed:production`) instead of a shell string with a
 * namespaced `--class=Database\Seeders\ProductionSeeder` argument. That
 * argument's backslashes are exactly the kind of thing that silently
 * doesn't survive being re-quoted by whatever shell layer a given deploy
 * platform runs the command through — using ProductionSeeder::class here
 * sidesteps the whole question instead of trying to get the escaping right
 * for one specific platform's shell.
 */
class SeedProduction extends Command
{
    protected $signature = 'db:seed:production';

    protected $description = 'Seed only the reference/structural data a real deployment needs (see ProductionSeeder) — never the sample catalog.';

    public function handle(): int
    {
        return $this->call('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ]);
    }
}
