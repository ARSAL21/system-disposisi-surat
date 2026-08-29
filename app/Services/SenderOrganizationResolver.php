<?php

namespace App\Services;

use App\Models\SenderOrganization;

class SenderOrganizationResolver
{
    /**
     * @param  array{mode: 'existing', id: int}|array{mode: 'new', name: string, address: string|null, contact: string|null}  $selection
     */
    public function resolveForRegistration(array $selection): SenderOrganization
    {
        if ($selection['mode'] === 'existing') {
            return SenderOrganization::query()
                ->whereKey($selection['id'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $organization = new SenderOrganization;
        $organization->name = trim($selection['name']);
        $organization->address = $this->nullableTrim($selection['address']);
        $organization->contact = $this->nullableTrim($selection['contact']);
        $organization->is_active = true;
        $organization->save();

        return $organization;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
