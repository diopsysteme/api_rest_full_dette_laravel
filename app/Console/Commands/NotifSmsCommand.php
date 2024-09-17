<?php

namespace App\Console\Commands;

use App\Jobs\SendUnpaidDebtNotificationsJob;
use Illuminate\Console\Command;

class NotifSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:forall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS notifications to clients with unpaid debts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Dispatch the job to send notifications
        SendUnpaidDebtNotificationsJob::dispatch();

        $this->info('Unpaid debt notifications have been dispatched.');
    }
}
