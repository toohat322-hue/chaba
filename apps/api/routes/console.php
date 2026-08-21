<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly DB + media backup (config/backup.php), off-peak. `backup:run` alone
// covers a fresh dev/staging box with zero setup (BACKUP_DISKS defaults to
// 'local'); add 's3' once a real off-server target is configured.
Schedule::command('backup:run')->dailyAt('03:00')->onOneServer();
Schedule::command('backup:clean')->dailyAt('03:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('04:00')->onOneServer();

// Reminds a registered customer once per cart, at least 2 hours after they
// last touched it (see RemindAbandonedCarts's docblock).
Schedule::command('cart:remind-abandoned')->hourly()->onOneServer();
