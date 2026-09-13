<?php

namespace App\Http\Controllers\BackOffice\ExpertConsultations;

use App\ExpertConsultations\ExpertConsultationPresenter;
use App\Http\Controllers\Controller;
use App\Models\ExpertConsultation;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ExpertConsultationCoordinationController extends Controller
{
    public function __invoke(Request $request, ExpertConsultationPresenter $presenter): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        Gate::authorize('coordinate', ExpertConsultation::class);
        $sekdaPositionIds = $user->positionAssignments()->active()
            ->whereHas('position', fn ($query) => $query->where('code', OrganizationCatalog::REGIONAL_SECRETARY_POSITION))
            ->pluck('position_id')->all();
        $items = ExpertConsultation::query()->with(['expertPosition.organizationalUnit:id,name', 'requestedBy:id,name', 'requestedByPositionAssignment.position:id,name'])
            ->whereHas('expertPosition.sourceRelationships', fn ($query) => $query->whereIn('target_position_id', $sekdaPositionIds)->where('relationship_type', 'ADMINISTRATIVE_COORDINATION')->where('is_active', true))
            ->latest('requested_at')->latest('id')->get();

        return Inertia::render('back-office/expert-consultations/Coordination', ['consultations' => $items->map(fn (ExpertConsultation $item) => $presenter->coordination($item))->all(), 'capabilities' => ['can_view' => false, 'can_request' => false, 'can_respond' => false, 'can_coordinate' => true], 'routes' => ['index' => route('back-office.expert-consultations.index'), 'coordination' => route('back-office.expert-consultations.coordination')], 'preview' => false]);
    }
}
