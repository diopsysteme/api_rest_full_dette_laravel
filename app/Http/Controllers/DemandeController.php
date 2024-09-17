<?php

namespace App\Http\Controllers;

use Auth;
use Gate;
use App\Enums\CategoryEnum;
use Illuminate\Http\Request;
use App\Notifications\SendSmsNotif;
use App\Services\DemandeServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;

class DemandeController extends Controller
{
    protected $demandeService;

    public function __construct(DemandeServiceInterface $demandeService)
    {
        $this->demandeService = $demandeService;
    }

    public function store(Request $request)
    {
        try {
            // dd("ee");
            Gate::authorize('demande');
            $data = $request->all();
            return $this->demandeService->handleCreateDemande($data);
        } catch (AuthorizationException $e) {
            if (Auth::user()->client) {
                $message = auth()->user()->client->category_label == CategoryEnum::Bronze->value
                ? "vous avez une ou des dettes non soldes vous ne pouvez pas faire de demande desole"
                : "Vous avez atteint le montant max de cummul de dette. desole";
            } else {
                $message = "seul les client peuvent faire une demande";
            }

            return [
                "statut" => "echec",
                "message" => "$message",
                "code" => 403,
            ];
        }
    }
    public function index()
    {
        Gate::authorize("onlyclient");
        return $this->demandeService->getDemandes();
    }
    public function relance($id)
    {
        Gate::authorize("onlyclient");
        // dd('ss');
        return $this->demandeService->relance($id);
    }

    public function notifDemande()
    {
        Gate::authorize("onlyclient");
        return $this->demandeService->notifDemande();
    }
    public function getBoutiquierNotifications()
    {
        return $this->demandeService->getBoutiquierNotifications();
    }
    public function getAllDemandesForBoutiquier()
    {
        return $this->demandeService->getAllDemandesForBoutiquier();
    }
public function disponible($id){

    Gate::authorize("client");
    return $this->demandeService->disponible($id);
}
public function update(Request $request, $id)
{
    $demande = $this->demandeService->getDemandeById($id);
    if (!$demande) {
        return ['message' => 'Demande non trouvée'];
    }

    if ($demande->etat !== 'en_cours') {
        return ['message' => 'La demande est déjà traitée'];
    }

    $action = $request->input('action');

    // Validation de la demande
    if ($action === 'valider') {
        return $this->demandeService->traiterDemande($demande);
    }

    if ($action === 'annuler') {
        $motif = $request->input('motif')??"demande annule";

        $message = "Votre demande a été annulée pour la raison suivante : $motif.";
        $demande->client->notify(new SendSmsNotif($message));

        $demande->etat = 'annulee';
        $demande->save();

        return response()->json(['message' => 'Demande annulée avec succès']);
    }

    return response()->json(['message' => 'Action invalide'], 400);
}

public function valideThroughtIdRetour($id){
    return $this->demandeService->valideThroughtIdRetour($id);
}
// private function sendRelanceNotification($demande)
// {
//     // Assuming you have a notification system in place
//     // Send the notification to the boutiquier
//     $boutiquier = $demande->boutiquier; // Adjust according to your relationship
//     Notification::send($boutiquier, new RelanceNotification($demande));
// }

}
