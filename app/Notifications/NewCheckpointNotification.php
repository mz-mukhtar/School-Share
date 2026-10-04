<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCheckpointNotification extends Notification
{
    use Queueable;

    public Project $project;
    public User $uploader;
    public string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $uploader, string $message)
    {
        $this->project = $project;
        $this->uploader = $uploader;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'checkpoint',
            'project_id' => $this->project->id,
            'title'      => 'New Checkpoint in ' . $this->project->name,
            'message'    => "{$this->uploader->name} uploaded files: \"{$this->message}\"",
            'url'        => route('projects.show', $this->project),
            'icon'       => '📦',
        ];
    }
}
