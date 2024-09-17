<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SmsService; // Assuming Infobip service is available

class InfobipSmsChannel
{
    protected $infobipService;

    public function __construct(SmsService $infobipService)
    {
        $this->infobipService = $infobipService;
    }

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toInfobipSms($notifiable);

        $phoneNumber = $notifiable->routeNotificationFor('infobip_sms');
        // dd($phoneNumber);
        // Send the message
        if ($phoneNumber) {
            $this->infobipService->sendMessage("DIOP E-SHOP","+221".$phoneNumber, $message);
        }
    }
}
