<?php

namespace App\Services;

use App\Models\OutgoingLetterDelivery;
use App\Models\OutgoingLetterDeliveryLink;
use App\Models\OutgoingLetterDeliveryLinkRevocation;
use App\Models\PositionAssignment;
use App\Models\User;
use App\OutgoingLetters\IssuedOutgoingDeliveryLink;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

final class StandaloneOutgoingDeliveryLinkService
{
    public function issue(OutgoingLetterDelivery $delivery, string $email, User $actor, PositionAssignment $assignment): IssuedOutgoingDeliveryLink
    {
        $this->revokeActive($delivery, $actor, $assignment, 'REISSUED');
        $token = Str::random(64);
        $link = new OutgoingLetterDeliveryLink;
        $link->outgoing_letter_delivery_id = $delivery->getKey();
        $link->token_hash = hash('sha256', $token);
        $link->recipient_email = $email;
        $link->sent_at = Date::now();
        $link->expires_at = Date::now()->addDays((int) config('outgoing-letters.delivery_link_ttl_days', 7));
        $link->created_by_user_id = $actor->getKey();
        $link->created_by_position_assignment_id = $assignment->getKey();
        $link->save();

        return new IssuedOutgoingDeliveryLink($link, $token);
    }

    public function revokeActive(OutgoingLetterDelivery $delivery, User $actor, PositionAssignment $assignment, string $reason): bool
    {
        $links = OutgoingLetterDeliveryLink::query()
            ->where('outgoing_letter_delivery_id', $delivery->getKey())
            ->with('revocation')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        $revoked = false;
        foreach ($links as $link) {
            if ($link->revocation !== null || $link->expires_at->isPast()) {
                continue;
            }

            $entry = new OutgoingLetterDeliveryLinkRevocation;
            $entry->outgoing_letter_delivery_link_id = $link->getKey();
            $entry->reason = $reason;
            $entry->revoked_by_user_id = $actor->getKey();
            $entry->revoked_by_position_assignment_id = $assignment->getKey();
            $entry->revoked_at = Date::now();
            $entry->save();
            $revoked = true;
        }

        return $revoked;
    }
}
