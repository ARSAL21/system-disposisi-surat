<?php

namespace App\Http\Controllers\BackOffice\Workflow;

use App\Actions\BuildMutationSecurityState;
use App\Actions\ChangeInstructionLabelStatus;
use App\Actions\CreateInstructionLabel;
use App\Actions\UpdateInstructionLabel;
use App\Dispositions\DispositionPresenter;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Workflow\ChangeInstructionLabelStatusRequest;
use App\Http\Requests\BackOffice\Workflow\ListInstructionLabelsRequest;
use App\Http\Requests\BackOffice\Workflow\StoreInstructionLabelRequest;
use App\Http\Requests\BackOffice\Workflow\UpdateInstructionLabelRequest;
use App\Models\InstructionLabel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstructionLabelController extends Controller
{
    public function index(
        ListInstructionLabelsRequest $request,
        DispositionPresenter $presenter,
        BuildMutationSecurityState $buildSecurityState,
    ): Response {
        $filters = $request->filters();
        $query = InstructionLabel::query();

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';
            $query->where(function (Builder $search) use ($pattern): void {
                $search
                    ->where('code', 'like', $pattern)
                    ->orWhere('name', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern);
            });
        }

        if ($filters['status'] !== 'all') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $labels = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('back-office/workflow/instruction-labels/Index', [
            'labels' => $labels
                ->map(fn (InstructionLabel $label): array => $presenter->instructionLabel($label))
                ->values()
                ->all(),
            'activeLabelCount' => InstructionLabel::query()
                ->where('is_active', true)
                ->count(),
            'filters' => $filters,
            'mutationSecurity' => $buildSecurityState->execute(
                $request,
                PermissionName::ManageDispositionInstructions,
                route('back-office.workflow.mutation.confirm'),
            ),
            'routes' => [
                'index' => route('back-office.workflow.instruction-labels.index'),
                'store' => route('back-office.workflow.instruction-labels.store'),
            ],
            'preview' => false,
        ]);
    }

    public function store(
        StoreInstructionLabelRequest $request,
        CreateInstructionLabel $create,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $create->execute($actor, $request->validatedPayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Instruksi disposisi berhasil ditambahkan.',
        ]);

        return back();
    }

    public function update(
        UpdateInstructionLabelRequest $request,
        InstructionLabel $instructionLabel,
        UpdateInstructionLabel $update,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $update->execute($actor, $instructionLabel, $request->validatedPayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Instruksi disposisi berhasil diperbarui.',
        ]);

        return back();
    }

    public function status(
        ChangeInstructionLabelStatusRequest $request,
        InstructionLabel $instructionLabel,
        ChangeInstructionLabelStatus $changeStatus,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $label = $changeStatus->execute(
            $actor,
            $instructionLabel,
            $request->boolean('is_active'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $label->is_active
                ? 'Instruksi disposisi diaktifkan.'
                : 'Instruksi disposisi dinonaktifkan.',
        ]);

        return back();
    }
}
