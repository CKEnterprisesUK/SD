<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SiteDeskResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Set your SiteDesk password')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have been invited to access SiteDesk.')
            ->line('Use the button below to set your password and sign in.')
            ->action('Set password', $url)
            ->line('This link will expire for security reasons.')
            ->line('If you were not expecting this email, you can ignore it.');
    }
}