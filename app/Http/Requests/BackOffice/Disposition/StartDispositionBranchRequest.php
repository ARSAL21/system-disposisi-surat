<?php

namespace App\Http\Requests\BackOffice\Disposition;

use App\Models\DispositionRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StartDispositionBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipient = $this->route('dispositionRecipient');

        if (! $recipient instanceof DispositionRecipient) {
            return false;
        }

        Gate::authorize('startBranch', $recipient);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
