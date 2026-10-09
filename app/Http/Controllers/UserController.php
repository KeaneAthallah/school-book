<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'users.view'), 403);

        $search = $request->string('search')->toString();

        $users = User::query()
            ->with('roles:id,name,slug')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
                'role_ids' => $user->roles->pluck('id')->all(),
            ]);

        return view('settings.users', [
            'users' => $users,
            'filters' => ['search' => $search],
            'roles' => Role::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Role $role): array => ['value' => $role->getKey(), 'label' => $role->name])->all(),
            'can' => ['manage' => $this->userCan($request, 'users.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->roles()->sync($validated['roles']);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'password' => ['nullable', 'string', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->roles()->sync($validated['roles']);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->userCan($request, 'users.manage'), 403);
        abort_if($request->user()->getKey() === $user->getKey(), 403, 'Tidak dapat menghapus akun sendiri.');

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
