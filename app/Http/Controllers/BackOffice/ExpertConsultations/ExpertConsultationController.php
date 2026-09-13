<?php

namespace App\Http\Controllers\BackOffice\ExpertConsultations;

use App\Actions\ReportExpertConsultation;
use App\ExpertConsultations\ExpertConsultationPositionResolver;
use App\ExpertConsultations\ExpertConsultationPresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\ExpertConsultations\ReportExpertConsultationRequest;
use App\Models\ExpertConsultation;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ExpertConsultationController extends Controller
{
    public function index(Request $request, ExpertConsultationPresenter $presenter, ExpertConsultationPositionResolver $resolver): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->can('expert-consultations.view'), 403);
        $expertIds = $user->positionAssignments()->active()->whereHas('position.positionLevel', fn ($q) => $q->where('code', OrganizationCatalog::EXPERT_ADVISOR_LEVEL))->pluck('position_id')->all();
        $mayorIds = $user->positionAssignments()->active()->whereHas('position.positionLevel', fn ($q) => $q->where('code', OrganizationCatalog::MAYOR_LEVEL))->pluck('position_id')->all();
        abort_unless($expertIds !== [] || $mayorIds !== [], 404);
        $consultations = ExpertConsultation::query()->with($this->relations())
            ->where(fn ($query) => $query->whereIn('expert_position_id', $expertIds)->orWhereIn('letter_route_id', function ($routes) use ($mayorIds): void {
                $routes->select('id')->from('letter_routes')->whereIn('recipient_position_id', $mayorIds);
            }))
            ->latest('requested_at')->latest('id')->get();

        return Inertia::render('back-office/expert-consultations/Index', ['consultations' => $consultations->map(fn (ExpertConsultation $item) => $presenter->detail($item))->all(), 'filters' => ['search' => '', 'status' => ''], 'capabilities' => $this->capabilities($user, $resolver), 'routes' => ['index' => route('back-office.expert-consultations.index'), 'coordination' => route('back-office.expert-consultations.coordination')], 'preview' => false]);
    }

    public function show(Request $request, ExpertConsultation $expertConsultation, ExpertConsultationPresenter $presenter, ExpertConsultationPositionResolver $resolver): Response
    {
        Gate::authorize('view', $expertConsultation);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $expertConsultation->load($this->relations());

        return Inertia::render('back-office/expert-consultations/Show', ['consultation' => $presenter->detail($expertConsultation), 'capabilities' => $this->capabilities($user, $resolver), 'routes' => ['index' => route('back-office.expert-consultations.index')], 'preview' => false]);
    }

    public function report(ReportExpertConsultationRequest $request, ExpertConsultation $expertConsultation, ReportExpertConsultation $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $expertConsultation, (string) $request->validated('summary'), (string) $request->validated('recommendation'), $request->document());

        return to_route('back-office.expert-consultations.show', $expertConsultation)->with('success', 'Hasil telaah berhasil dikirim kepada Wali Kota.');
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return ['incomingLetter.senderOrganization:id,name', 'expertPosition.organizationalUnit:id,name', 'expertPosition.activeAssignment.user:id,name', 'requestedBy:id,name', 'requestedByPositionAssignment.position:id,name', 'report.reportedBy:id,name', 'report.reportedByPositionAssignment.position:id,name', 'documents'];
    }

    /** @return array<string, bool> */
    private function capabilities(User $user, ExpertConsultationPositionResolver $resolver): array
    {
        return ['can_view' => $user->can('expert-consultations.view'), 'can_request' => $user->can('expert-consultations.request'), 'can_respond' => $user->can('expert-consultations.respond'), 'can_coordinate' => $user->can('expert-consultations.coordinate') && $resolver->hasCoordinationAssignment($user)];
    }
}
