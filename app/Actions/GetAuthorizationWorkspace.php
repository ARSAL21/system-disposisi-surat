<?php

namespace App\Actions;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class GetAuthorizationWorkspace
{
    /**
     * @return array{roles: Collection<int, Role>, users: LengthAwarePaginator<int, User>}
     */
    public function execute(?string $userSearch): array
    {
        $roles = Role::query()
            ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
            ->with('permissions:id,name,guard_name')
            ->withCount('users')
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [RoleName::SuperAdmin->value])
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->where('account_type', AccountType::InternalAccount->value)
            ->when($userSearch, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with(['roles' => fn ($query) => $query
                ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
                ->orderBy('name')])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15, pageName: 'users_page')
            ->withQueryString();

        return ['roles' => $roles, 'users' => $users];
    }
}
