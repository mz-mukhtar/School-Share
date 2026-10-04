<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public Project $project;
    public User $inviter;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, User $inviter)
    {
        $this->project = $project;
        $this->inviter = $inviter;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Only in-app database notifications for now
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'invite',
            'project_id' => $this->project->id,
            'title'      => 'New Project Invitation',
            'message'    => "{$this->inviter->name} invited you to collaborate on '{$this->project->name}'.",
            'url'        => route('projects.show', $this->project),
            'icon'       => '🤝',
        ];
    }
}
