<?php
namespace App\Services;

use App\Models\Client;
use App\Notifications\SendSmsNotif;

class NotificationService implements NotificationServiceInterface{
    
    public function notifRappelFor($id){
        $client = Client::find($id);
         if(!$client){
             return ['message' => 'Client not found'];
         }
         $montantTotal = $client->solde;
     
         if ($montantTotal > 0 ) {
             $message = "Cher client, vous avez un total de dettes non soldées de : $montantTotal. Veuillez régulariser votre situation.";
     
             $client->notify(new SendSmsNotif($message));
     
             return ['message' => 'Notification envoyée avec succès'];
         }
     
         return ['message' => 'Le client n\'a pas de dettes non soldées'];
    }
    public function sendGroupDebtReminder($request){
        $clients = Client::whereIn('id', $request->id_clients)->get();
        // dd($clients);
        foreach ($clients as $client) {
            $montantTotal = $client->solde;
    
            if ($montantTotal > 0|| $request->body) {
                $message = $request->body?$request->body: "Cher client, vous avez un total de dettes non soldées de : $montantTotal. Veuillez régulariser votre situation.";
                $client->notify(new SendSmsNotif($message));
            }
        }
    
        return ['message' => 'Notifications envoyées avec succès'];
    }   
}