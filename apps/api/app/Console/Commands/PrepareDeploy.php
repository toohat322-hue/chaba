<?php

namespace App\Console\Commands;

use Database\Seeders\ProductionSeeder;
use Illuminate\Console\Command;

/**
 * Runs migrate + the production-safe seed set as one command, so a deploy
 * platform's pre-deploy/release-command hook needs no shell operators at
 * all. Started as just the seed half (`db:seed:production`), chaining it
 * after migrate with `&&` in Render's preDeployCommand — Render's Docker
 * runtime doesn't invoke that string through a shell that understands `&&`
 * (verified against a real deploy: it passed "&&" as a literal argument to
 * the migrate command and failed), so the two steps are combined here
 * instead of depending on shell chaining a platform may or may not provide.
 *
 * Also sidesteps a second, unrelated escaping problem the seed half had on
 * its own: passing a namespaced --class=Database\Seeders\ProductionSeeder
 * argument through an unknown number of shell layers doesn't reliably keep
 * its backslashes intact either (hit this directly while testing locally).
 * ProductionSeeder::class avoids that question entirely.
 */
class PrepareDeploy extends Command
{
    protected $signature = 'deploy:prepare';

    protected $description = 'Run migrations, then seed only the reference/structural data a real deployment needs (see ProductionSeeder) — never the sample catalog. One command, no shell operators required.';

    public function handle(): int
    {
        $migrateExit = $this->call('migrate', ['--force' => true]);

        if ($migrateExit !== self::SUCCESS) {
            return $migrateExit;
        }

        return $this->call('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ]);
    }
}
