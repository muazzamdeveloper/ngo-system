<?php

namespace App\Notifications;

use App\Models\OrganizationSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberAccountCreated extends Notification
{
    public function __construct(public readonly string $password)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organization = OrganizationSetting::current()->name;

        return (new MailMessage)
            ->subject("Your {$organization} member login")
            ->greeting("Hello {$notifiable->name},")
            ->line("A login account has been created for you at {$organization}.")
            ->line("Email: {$notifiable->email}")
            ->line("Temporary password: {$this->password}")
            ->action('Log in', route('login'))
            ->line('You will be asked to choose a new password the first time you log in. Please do not share this email.');
    }
}
