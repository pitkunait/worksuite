<?php

namespace App\Listeners;

use App\Events\FileUploadEvent;
use App\Notifications\FileUpload;
use App\Project;
use Illuminate\Support\Facades\Notification;

class FileUploadListener
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
     * @param  FileUploadEvent  $event
     * @return void
     */
    public function handle(FileUploadEvent $event)
    {
        $project = Project::findOrFail($event->fileUpload->project_id);
        Notification::send($project->members_many, new FileUpload($event->fileUpload));
    }
}
