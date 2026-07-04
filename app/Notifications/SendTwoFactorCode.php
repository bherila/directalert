<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SendTwoFactorCode extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if (app()->environment('local')) {
            // Full email is logged too (MAIL_MAILER=log), but it's a giant
            // HTML dump that `pail` truncates - this gives a short, visible
            // line with just the code for local dev.
            Log::debug("2FA code for {$notifiable->email}: {$notifiable->two_factor_code}");
        }

        return (new MailMessage)
            ->line("Your two-factor code is {$notifiable->two_factor_code}")
            ->action('Verify Here', route('verify.index'))
            ->line('The code will expire in 10 minutes')
            ->line('If you did not request this, please ignore.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
