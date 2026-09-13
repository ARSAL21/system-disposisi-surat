<?php

namespace App\Http\Controllers\BackOffice\ExpertConsultations;

use App\Http\Controllers\Controller;
use App\Models\ExpertConsultation;
use App\Models\ExpertConsultationDocument;
use App\Services\PrivateDocumentResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExpertConsultationDocumentController extends Controller
{
    public function preview(Request $request, ExpertConsultation $expertConsultation, ExpertConsultationDocument $expertConsultationDocument, PrivateDocumentResponse $response): StreamedResponse
    {
        Gate::authorize('view', $expertConsultation);
        abort_unless((int) $expertConsultationDocument->expert_consultation_id === (int) $expertConsultation->getKey(), 404);

        return $response->previewExpertConsultationDocument($expertConsultation, $expertConsultationDocument);
    }

    public function download(Request $request, ExpertConsultation $expertConsultation, ExpertConsultationDocument $expertConsultationDocument, PrivateDocumentResponse $response): StreamedResponse
    {
        Gate::authorize('view', $expertConsultation);
        abort_unless((int) $expertConsultationDocument->expert_consultation_id === (int) $expertConsultation->getKey(), 404);

        return $response->downloadExpertConsultationDocument($expertConsultation, $expertConsultationDocument);
    }
}
