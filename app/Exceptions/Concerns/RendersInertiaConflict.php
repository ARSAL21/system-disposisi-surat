<?php

namespace App\Exceptions\Concerns;

use App\Models\DispositionRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

trait RendersInertiaConflict
{
    public function render(Request $request): Response
    {
        if ($request->hasHeader('X-Inertia')) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $this->getMessage(),
            ]);

            $recipient = $request->route('dispositionRecipient');
            $redirect = $recipient instanceof DispositionRecipient
                ? redirect()->route('back-office.dispositions.inbox.show', $recipient)
                : redirect()->back();

            return $redirect->withErrors([
                'workflow' => $this->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return response($this->getMessage(), Response::HTTP_CONFLICT, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
