<?php

namespace App\OutgoingLetters;

use App\Models\OutgoingLetterDeliveryLink;

final readonly class IssuedOutgoingDeliveryLink
{
    public function __construct(
        public OutgoingLetterDeliveryLink $link,
        public string $plainToken,
    ) {}
}
