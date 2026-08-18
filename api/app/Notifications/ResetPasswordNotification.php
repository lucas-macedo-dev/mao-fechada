<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('services.frontend_url', 'http://localhost:5173'), '/');
        $resetUrl = $frontendUrl.'/auth/reset-password'
            .'?token='.$this->token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject(__('messages.email_reset_subject'))
            ->view('emails.reset-password', [
                'url'           => $resetUrl,
                'name'          => $notifiable->name,
                'expireMinutes' => config('auth.passwords.users.expire', 60),
            ]);
    }
}
