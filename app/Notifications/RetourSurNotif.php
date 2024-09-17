<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetourSurNotif extends Notification implements ShouldQueue
{
    use Queueable;

    protected $disponible;

    /**
     * Create a new notification instance.
     *
     * @param array $disponible
     */
    public function __construct($disponible)
    {
        $this->disponible = $disponible;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail' ,'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Articles Disponibles pour Votre Demande')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Voici les articles disponibles pour votre demande.')
            ->action('Voir les articles disponibles', url('/demandes/disponible/' . $this->disponible['id']));

        foreach ($this->disponible as $article) {
            if (is_array($article)) {
                $mailMessage->line($article['libelle'] . ': ' . $article['quantite'] . ' x ' . $article['prix'] . ' FCFA');
            }
        }

        $mailMessage->line('Montant total: ' . $this->disponible['montant_total'] . ' FCFA');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            "article dispo"=>$this->disponible,
    "message"=>"Articles Disponibles pour votre Demande"];
    }
}