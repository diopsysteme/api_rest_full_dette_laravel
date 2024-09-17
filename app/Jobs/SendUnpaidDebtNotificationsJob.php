<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\DebtService;
use Illuminate\Bus\Queueable;
use App\Services\DetteService;
use App\Notifications\SendSmsNotif;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendUnpaidDebtNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $debtService;

    /**
     * Create a new job instance.
     *
     * @param DetteService $debtService
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $clients = Client::all();

        $clientsDImpaye = array_filter($clients->all(), function($client) {
            return $client->dettes_count > 0;
        });
        
        foreach ($clientsDImpaye as $client) {
            $totalDueAmount = $client->solde;
            $nom= $client->telephone;
            $message = "$nom Vous avez un montant total de dettes non soldées de : " . number_format($totalDueAmount, 2) . "FCFA";

            $client->notify(new SendSmsNotif($message));

            \Log::info('SMS notification sent to client', ['client_id' => $client->id, 'message' => $message]);
        }
    }
}
