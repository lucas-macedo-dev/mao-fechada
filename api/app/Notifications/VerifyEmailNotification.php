<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        $apiSignedUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            compact('id', 'hash')
        );

        $parsed = parse_url($apiSignedUrl);
        parse_str($parsed['query'] ?? '', $queryParams);

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return $frontendUrl . '/auth/verify-email/' . $id . '/' . $hash
            . '?expires=' . ($queryParams['expires'] ?? '')
            . '&signature=' . ($queryParams['signature'] ?? '');
    }

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->line('Click the button below to verify your email address. This link expires in 60 minutes.')
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not create an account, no action is required.');
    }
}
