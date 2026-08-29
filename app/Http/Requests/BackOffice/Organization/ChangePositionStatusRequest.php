<?php

namespace App\Http\Requests\BackOffice\Organization;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;

class ChangePositionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position && $this->user()?->can('changeStatus', $position) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
