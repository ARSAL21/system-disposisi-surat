<?php

namespace App\Http\Requests\BackOffice\Routing;

use App\Models\IncomingLetter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreLetterRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incomingLetter = $this->route('incomingLetter');

        if (! $incomingLetter instanceof IncomingLetter) {
            return false;
        }

        Gate::authorize('createRoute', $incomingLetter);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'target_position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id'),
            ],
        ];
    }

    public function targetPositionId(): int
    {
        return (int) $this->validated('target_position_id');
    }
}
