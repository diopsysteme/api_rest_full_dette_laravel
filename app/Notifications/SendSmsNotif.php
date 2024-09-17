<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Channels\TwilioSmsChannel;
use App\Channels\InfobipSmsChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendSmsNotif extends Notification
{
    use Queueable;
    protected $message;
    /**
     * Create a new notification instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return [ InfobipSmsChannel::class,TwilioSmsChannel::class, 'database']; // Add other channels as needed
    }
    
    public function toInfobipSms($notifiable)
    {
        // dd($this->message);
        return $this->message;
    }
    public function toTwilioSms($notifiable)
    {
        return $this->message;
    }

    // Format the message for Infobip
    public function toArray(object $notifiable): array
    {
        return [
            "message" => $notifiable,
            "message2" => $this->message,
        ];
    }
}
