<?php
namespace App\Services;
interface NotificationServiceInterface{
    public function notifRappelFor($id);
    public function sendGroupDebtReminder($request);
}
