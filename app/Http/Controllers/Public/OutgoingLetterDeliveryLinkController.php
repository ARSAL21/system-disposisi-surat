<?php

namespace App\Http\Controllers\Public;

use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetterDeliveryLink;
use App\Services\PrivateDocumentResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OutgoingLetterDeliveryLinkController extends Controller
{
    public function __invoke(string $token, PrivateDocumentResponse $response): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1, 404);
        $link = OutgoingLetterDeliveryLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->whereDoesntHave('revocation')
            ->with('delivery.outgoingLetter.currentDocumentVersion')
            ->firstOrFail();
        $letter = $link->delivery->outgoingLetter;
        $version = $letter->currentDocumentVersion;
        abort_unless(
            $letter->origin === OutgoingLetterOrigin::Standalone
            && $letter->status === OutgoingLetterStatus::Delivered
            && $version !== null,
            404,
        );

        return $response->downloadOutgoingLetterDocument($letter, $version);
    }
}
