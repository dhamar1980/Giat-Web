<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtpNotification extends Notification
{
    use Queueable;

    public string $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
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
        return (new MailMessage)
            ->subject('Permintaan Reset Password - GIAT Web')
            ->greeting('Halo ' . ($notifiable->nama ?? $notifiable->name ?? 'Pengguna') . ',')
            ->line('Kami menerima permintaan untuk mereset password akun GIAT Anda.')
            ->line('Gunakan kode OTP berikut untuk mengatur ulang password Anda:')
            ->line('**' . $this->otp . '**')
            ->line('Kode ini berlaku selama 60 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan pesan ini.')
            ->salutation('Salam hangat, Tim GIAT Web');
    }
}
