<?php

namespace App\Notifications;

use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StandaloneOutgoingInternalCopyAvailable extends Notification
{
    use Queueable;

    public function __construct(private readonly OutgoingLetter $letter) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tembusan surat keluar')
            ->line('Anda menerima tembusan internal untuk surat "'.$this->letter->subject.'".')
            ->line('Dokumen tetap berada di back-office dan memerlukan akun internal untuk dibuka.')
            ->action('Buka register surat keluar', route('back-office.outgoing-letters.show', $this->letter));
    }
}
