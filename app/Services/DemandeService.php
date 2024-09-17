<?php

// app/Services/DemandeService.php

namespace App\Services;

use App\Exceptions\ServiceException;
use Gate;
use App\Models\Dette;
use App\Models\Client;
use App\Models\Article;
use App\Models\Demande;
use App\Enums\EtatDetteEnum;
use Illuminate\Support\Facades\DB;
use App\Notifications\SendSmsNotif;
use Illuminate\Support\Facades\Auth;
use App\Notifications\RetourSurNotif;
use App\Notifications\ValidCancelNotif;
use App\Jobs\NotifyBoutiquiersOfNewDemande;
use Illuminate\Support\Facades\Notification;
use App\Repository\DemandeRepositoryInterface;

class DemandeService implements DemandeServiceInterface
{
    protected $demandeRepository;

    public function __construct(DemandeRepositoryInterface $demandeRepository)
    {
        $this->demandeRepository = $demandeRepository;
    }
    public function handleCreateDemande(array $data)
    {
        $clientId = auth()->user()->client->id;
        // $clientId = 182;
        $totalMontant = 0;
        $validatedArticles = [];
        $invalidArticles = [];

        foreach ($data['articles'] as $articleData) {
            $article = Article::find($articleData['id']);

            if (!$article || $articleData['qte_vente'] <= 0) {
                $invalidArticles[] = [
                    'article_id' => $articleData['id'],
                    'message' => !$article ? 'Article non trouvé' : 'Quantité invalide',
                ];
                continue;
            } else {
                if ($article->stock >= $articleData['qte_vente']) {
                    $validatedArticles[] = [
                        'article_id' => $article->id,
                        'quantite' => $articleData['qte_vente'],
                        'montant' => 1,
                    ];
                    
                } else {
                    $validatedArticles[] = [
                        'article_id' => $article->id,
                        'quantite' => $articleData['qte_vente'],
                        'montant' => 0,
                    ];
                }
            }
            $totalMontant += $article->prix * $articleData['qte_vente'];
        }
        // dd($totalMontant);
        $demande = $this->demandeRepository->createDemandeWithArticles($clientId, $totalMontant, $validatedArticles);

        return [
            'demande' => $demande,
            'invalid_articles' => $invalidArticles,
        ];
    }
    public function createDemande(array $data)
    {
        return $this->demandeRepository->create($data);
    }

    public function updateDemande($id, array $data)
    {
        return $this->demandeRepository->update($id, $data);
    }
    public function getDemandeById($id)
    {
        return $this->demandeRepository->find($id);
    }
    public function getDemandes()
    {
        return $this->demandeRepository->getDemandes();
    }
    public function relance($id){
        $demande = Demande::find($id);
        Gate::authorize("ableToAccess", $demande);
        if (!$demande || $demande->etat !== EtatDetteEnum::annule->value) {
            return ['message' => 'La demande n\'est pas annulée ou n\'existe pas'];
        }
        $updatedAt = $demande->updated_at;
        if (now()->diffInDays($updatedAt) > 2) {
            return ['message' => 'La relance est impossible, délai de 2 jours dépassé'];
        }
        $demande->etat = EtatDetteEnum::en_cours->value;
        $demande->save();
        NotifyBoutiquiersOfNewDemande::dispatch($demande);
        return ['message' => 'La demande a été relancée avec succès'];
    }
    public function notifDemande(): array{
        $client = auth()->user()->client;

        if (!$client) {
            return ['message' => 'Client not found'];
        }
    
        $notifications = $client->user->notifications()->where('type', 'App\Notifications\RetourSurNotif')->get();
    
        return [
            'notifications' => $notifications
        ];
    }
public function getBoutiquierNotifications(){
Gate::authorize("client");

    $notifications = auth()->user()->notifications()->where('type', 'App\Notifications\NewDemandeSubmitted')->get();

    return[
        'notifications' => $notifications
    ];
}
public function getAllDemandesForBoutiquier(){
    // dd("dd");
    Gate::authorize("client");
    // dd("dd");
    return $this->demandeRepository->getAllDemandesForBoutiquier();
}
public function disponible($id) {
    Gate::authorize("client");

    $demande = $this->demandeRepository->find($id);

    if (!$demande || $demande->etat == EtatDetteEnum::annule->value) {
        return ['message' => 'La demande a été annulée'];
    }
    $data=$this->valideDemande($demande);
    // dd($data);
    $disponible = $data['dispo'];
    $indisponible = $data['indispo'];
    $montant = $data['montant'];

    if (!empty($disponible) && !empty($indisponible)) {
        // dd("dd");
        $disponible["montant_total"] = $montant;

        $demande->client->user->notify(new RetourSurNotif($disponible));
        $message="notification sent ";
    }


    return [
        'message' => 'La demande a été traitée avec succès '.$message,
        'disponible' => $disponible,
        'indisponible' => $indisponible
    ];
}
public function traiterDemande($demande)
{
    DB::beginTransaction();

    try {
        $client = $demande->client;

        $data = $this->valideDemande($demande);
        $articlesDisponibles = $data['dispo'];
        $articlesNonDisponibles = $data['indispo'];

        if (!empty($articlesNonDisponibles)) {
            $message = "Certains articles ne sont pas disponibles. Voici les articles disponibles : " . implode(', ', array_column($articlesDisponibles, 'libelle')) . ". La demande est annulée.";
            $client->user->notify(new ValidCancelNotif($message));
            $demande->etat = EtatDetteEnum::annule->value;
            $demande->save();
            DB::commit();
            return ["dette annulée à cause de l'indisponibilité des articles"];
        }

        $dette = new Dette();
        $dette->client_id = $client->id;
        $dette->montant = $data['montant'];
        $dette->date = now();
        $dette->user_id = auth()->id();
        $dette->date_echeance = now()->addDays(env('LIMITEDAY', 15));
        $dette->save();

        foreach ($articlesDisponibles as $articleDemande) {
            $dette->articles()->attach($articleDemande['id'], [
                'qte_vente' => $articleDemande['quantite'],
                'prix_vente' => $articleDemande['prix']
                
            ]);
            // dd($articleDemande);ss
            $article = Article::find($articleDemande['id']);
            // dd($article);
            $article->qtstock -= $articleDemande['quantite'];
            $article->save();
        }

        $demande->delete();

        DB::commit();

        $client->user->notify(new ValidCancelNotif("Votre dette a été créée avec succès pour les articles demandés. Veuillez passer récupérer vos produits."));

        return ["dette enregistrée"];
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

public function valideDemande($demande){
    $disponible = [];
    $indisponible = [];
    $montant = 0;

    foreach ($demande->articles as $article) {
        if ($article->quantite_dispo >= $article->pivot->quantite) {
            $disponible[] = [
                "libelle" => $article->label,
                'id' => $article->id,
                'quantite' => $article->pivot->quantite,
                'prix' => $article->prix,
            ];
            $montant += $article->pivot->quantite * $article->prix;
        } else {
            $indisponible[] = [
                "libelle" => $article->label,
                'id' => $article->id,
                'quantite_dispo' => $article->quantite_dispo,
                'quantite_demandee' => $article->pivot->quantite,
            ];
        }
    }
    return["dispo"=>$disponible,"indispo"=>$indisponible,"montant"=>$montant];

}
public function valideThroughtIdRetour($id)
{
    DB::beginTransaction();

    try {
        $notification = auth()->user()->notifications()->find($id);
        
        if (!$notification || $notification->type !== RetourSurNotif::class) {
            return response()->json(['error' => 'Notification introuvable ou invalide'], 404);
        }
        
        $articlesDisponibles = $notification->data['article dispo'];
        if (empty($articlesDisponibles)) {
            return response()->json(['error' => 'Aucun article disponible dans la notification'], 400);
        }

        $client = auth()->user();

        $dette = new Dette();
        $dette->client_id = $client->id;
        $dette->montant = $articlesDisponibles["montant_total"];
        $dette->date = now();
        $dette->user_id =$articlesDisponibles["user_id"] ??auth()->id();
        $dette->date_echeance = now()->addDays(env('LIMITEDAY', 15));
        $dette->save();

        // dd($articlesDisponibles);
        foreach (collect($articlesDisponibles)->except(["montant_total"]) as $article) {
            $dette->articles()->attach($article['id'], [
                 'qte_vente'=> $article['quantite'],
                'prix_vente'=>$article['prix']

            ]);
            $articleModel = Article::find($article['id']);
            if(!$articleModel||$articleModel->quantite_dispo<$article['quantite']){
                $notification->delete();
                throw new ServiceException("cette demande ne peux plus etre enregistrer a present l'article n'est plus dispo  la notification va etre supprimer veuillez faire une autre demande");
            }
            $articleModel->qtstock -= $article['quantite'];
            $articleModel->save();
        }
        
        $notification->delete();
        
        DB::commit();

        $client->notify(new ValidCancelNotif("Votre validation a été prise en compte. Votre dette a été créée pour les articles validés."));
        
        return ["messsage"=>"dette enregistrée avec succès"];

    }
    catch (\Exception $e) {
        DB::rollBack();
        return ['error' => 'Notification non trouvée'.$e];
    }
}


}
