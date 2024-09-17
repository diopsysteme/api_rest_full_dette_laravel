<?php

namespace App\Jobs;

use App\Models\Client;
use App\Notifications\SendSmsNotif;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $clients = Client::all();

        $clientsDImpaye = array_filter($clients->all(), function($client) {
            return $client->dettes_count > 0;
        });
        
        foreach ($clientsDImpaye as $client) {
            $dettesRestantes = $client->dettes->filter(function($dette) {
                return !$dette->etat_solde && $dette->date_echeance < Carbon::now();
            });
            foreach ($dettesRestantes as $dette) {
                $montantRestant = $dette->montant;
                $message = "$client->surnom ,Cher client, votre dette de $montantRestant est en retard. Veuillez la payer dès que possible.";
                $client->notify(new SendSmsNotif($message));
            }
        }
    }
}
