<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Подтверждение почты — PC Configurator')
            ->greeting('Привет, ' . $notifiable->name . '!')
            ->line('Спасибо за регистрацию. Чтобы активировать аккаунт, нажмите кнопку ниже.')
            ->action('Подтвердить почту', $url)
            ->line('Ссылка действует 60 минут.')
            ->line('Если вы не регистрировались — просто проигнорируйте письмо.');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}