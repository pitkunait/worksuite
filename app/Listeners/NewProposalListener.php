<?php

namespace App\Listeners;

use App\Events\NewInvoiceEvent;
use App\Events\NewProposalEvent;
use App\Notifications\NewInvoice;
use App\Invoice;
use App\Notifications\NewProposal;
use Illuminate\Support\Facades\Notification;

class NewProposalListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewInvoiceEvent  $event
     * @return void
     */
    public function handle(NewProposalEvent $event)
    {
        Notification::send($event->notifyUser, new NewProposal($event->proposal));
    }
}
