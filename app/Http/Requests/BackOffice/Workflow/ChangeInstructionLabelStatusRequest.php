<?php

namespace App\Http\Requests\BackOffice\Workflow;

use App\Models\InstructionLabel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ChangeInstructionLabelStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $label = $this->route('instructionLabel');

        if (! $label instanceof InstructionLabel) {
            return false;
        }

        Gate::authorize('update', $label);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
