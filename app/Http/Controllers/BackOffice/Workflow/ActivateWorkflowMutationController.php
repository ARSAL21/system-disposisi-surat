<?php

namespace App\Http\Controllers\BackOffice\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ActivateWorkflowMutationController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mode perubahan konfigurasi alur aktif selama 15 menit.',
        ]);

        return to_route('back-office.workflow.instruction-labels.index');
    }
}
