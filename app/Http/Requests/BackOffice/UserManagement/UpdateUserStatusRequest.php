<?php

namespace App\Http\Requests\BackOffice\UserManagement;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('managedUser');

        return $actor instanceof User
            && $target instanceof User
            && $actor->can('manageStatus', $target);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'reason' => ['required_if:is_active,false', 'nullable', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required_if' => 'Alasan penonaktifan wajib diisi minimal 10 karakter.',
            'reason.min' => 'Alasan penonaktifan minimal 10 karakter.',
            'reason.max' => 'Alasan penonaktifan maksimal 1000 karakter.',
        ];
    }
}
