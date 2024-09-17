<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\NotificationService;
use App\Services\NotificationServiceInterface;
use Illuminate\Http\Request;
use App\Notifications\SendSmsNotif;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendToGroupRequest;

class NotificationController extends Controller
{
    protected $notif;
    public function __construct(NotificationServiceInterface $notif){
        $this->notif = $notif;
    }
    /**
     * Display a listing of the resource.
     */
   
     public function notifRappelFor($id)
     {
        return $this->notif->notifRappelFor($id);
     }
     public function sendGroupDebtReminder(SendToGroupRequest $request): array
{
    return $this->notif->sendGroupDebtReminder($request);
}

   
     public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
