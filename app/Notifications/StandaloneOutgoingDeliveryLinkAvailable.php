<?php

namespace App\Notifications;

use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class StandaloneOutgoingDeliveryLinkAvailable extends Notification
{
    use Queueable;

    public function __construct(private readonly OutgoingLetter $letter, private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Surat resmi telah dikirim')
            ->greeting('Yth. '.$this->letter->standaloneDraft?->recipient_name.',')
            ->line('Surat resmi "'.$this->letter->subject.'" telah dikirim oleh '.$this->letter->standaloneDraft?->organizationalUnit?->name.'.')
            ->line('Untuk keamanan, PDF tidak dilampirkan pada email ini.')
            ->action('Unduh surat resmi', route('public.outgoing-letter-delivery-links.download', ['token' => $this->token]))
            ->line('Tautan ini berlaku selama tujuh hari dan dapat dicabut oleh pengirim.');
    }
}
