<?php

namespace App\Notifications;

use App\Models\TenantUserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TenantUserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private TenantUserInvitation $invitation,
        private string $plainToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'tenant-invitations.show',
            $this->invitation->expires_at,
            ['token' => $this->plainToken],
        );

        return (new MailMessage)
            ->subject('Business access invitation')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to join '.$this->invitation->tenant->name.'.')
            ->line('Your business username will be '.$this->invitation->username.'.')
            ->action('Review invitation', $url)
            ->line('This invitation expires in 48 hours.');
    }
}
