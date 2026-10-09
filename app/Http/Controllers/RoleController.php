<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * The one role that must keep every permission: locking it prevents an
     * administrator from removing their own ability to administer the system.
     */
    private const ADMIN_SLUG = 'admin';

    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'roles.manage'), 403);

        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()
            ->groupBy('group')
            ->map(fn ($group, string $name): array => [
                'group' => $name,
                'permissions' => $group->map(fn (Permission $permission): array => [
                    'id' => $permission->getKey(),
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $roles = Role::query()
            ->with('permissions:id')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->getKey(),
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'locked' => $role->slug === self::ADMIN_SLUG,
                'users_count' => (int) $role->getAttribute('users_count'),
                'permission_ids' => $role->permissions->pluck('id')->all(),
            ])
            ->all();

        return view('settings.roles', [
            'roles' => $roles,
            'permissionGroups' => $permissions,
            'permissionCount' => Permission::query()->count(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($this->userCan($request, 'roles.manage'), 403);
        abort_if($role->slug === self::ADMIN_SLUG, 403, 'Peran administrator tidak dapat diubah.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ])->save();

        $role->permissions()->sync($validated['permissions'] ?? []);

        return back()->with('success', 'Peran '.$role->name.' diperbarui.');
    }
}
