<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SmsService2; // Assuming your Twilio service is here

class TwilioSmsChannel
{
    protected $smsService;

    public function __construct(SmsService2 $smsService)
    {
        $this->smsService = $smsService;
    }

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toTwilioSms($notifiable);

        $phoneNumber = $notifiable->routeNotificationFor('twilio_sms');
        
        if ($phoneNumber) {
            $this->smsService->sendMessage($phoneNumber, "+221".$phoneNumber, $message);
        }
    }
}
