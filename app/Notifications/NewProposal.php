<?php

namespace App\Notifications;

use App\Estimate;
use App\Http\Controllers\Admin\ProposalController;
use App\Proposal;
use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewProposal extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    private $proposal;
    public function __construct(Proposal $proposal)
    {
        $this->proposal = $proposal;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database','mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = '';
        $proposalController = new ProposalController();
        if ($pdfOption = $proposalController->domPdfObjectForDownload($this->proposal->id)) {
            $pdf = $pdfOption['pdf'];
            $filename = $pdfOption['fileName'];

            return (new MailMessage)
                ->subject('New Lead Proposal Sent!')
                ->greeting('Hello ' . ucwords($this->proposal->lead->client_name) . '!')
                ->line('A new lead proposal has been sent to you. Login now to view the proposal.')
                ->action('Login To Dashboard', $url)
                ->attachData($pdf->output(), $filename . '.pdf');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return $this->proposal->toArray();
    }
}
