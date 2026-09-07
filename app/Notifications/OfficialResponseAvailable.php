<?php

namespace App\Notifications;

use App\Models\LetterSubmission;
use App\Models\OutgoingLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OfficialResponseAvailable extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OutgoingLetter $outgoingLetter,
        private readonly LetterSubmission $submission,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Balasan resmi tersedia')
            ->greeting('Yth. '.$this->submission->contact_name.',')
            ->line('Balasan resmi untuk pengajuan "'.$this->outgoingLetter->subject.'" telah tersedia.')
            ->line('Untuk keamanan dokumen, berkas tidak dilampirkan pada email ini.')
            ->action('Buka pengajuan', route('public.submissions.show', $this->submission))
            ->line('Silakan masuk menggunakan akun portal Anda untuk mengunduh dokumen resmi.');
    }
}
