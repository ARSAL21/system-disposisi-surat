<?php

namespace App\Http\Requests\BackOffice\OutgoingLetter;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class ApproveStandaloneOutgoingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    protected function passedValidation(): void
    {
        $user = $this->user();
        if (! $user instanceof User || ! $user->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages(['approval' => 'Aktifkan dan konfirmasi MFA sebelum mengesahkan surat.']);
        }

        $confirmedAt = (int) $this->session()->get('auth.password_confirmed_at', 0);
        if ($confirmedAt <= 0 || (time() - $confirmedAt) >= (int) config('auth.password_timeout', 10800)) {
            throw ValidationException::withMessages(['approval' => 'Konfirmasi kata sandi kembali sebelum mengesahkan surat.']);
        }
    }
}
