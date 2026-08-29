<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\PositionAssignment;
use Illuminate\Foundation\Http\FormRequest;

class EndPositionAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('positionAssignment');

        return $assignment instanceof PositionAssignment
            && $this->user()?->can('end', $assignment) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
