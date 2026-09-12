<?php

namespace App\Http\Requests\BackOffice\Routing;

use App\Enums\InitialLetterRoutePath;
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
            'route_path' => [
                'required',
                'string',
                Rule::enum(InitialLetterRoutePath::class),
            ],
        ];
    }

    public function routePath(): InitialLetterRoutePath
    {
        return InitialLetterRoutePath::from((string) $this->validated('route_path'));
    }
}
