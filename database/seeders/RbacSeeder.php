<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and one default account per role.
     */
    public function run(): void
    {
        /** @var array<string, array{name: string, group: string}> $permissions */
        $permissions = config('rbac.permissions');

        foreach ($permissions as $slug => $meta) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $meta['name'], 'group' => $meta['group']],
            );
        }

        $permissionIds = Permission::query()->pluck('id', 'slug');

        /** @var array<string, array{name: string, description: string, permissions: list<string>}> $roles */
        $roles = config('rbac.roles');

        foreach ($roles as $slug => $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_system' => true,
                ],
            );

            $slugs = $roleData['permissions'] === ['*']
                ? $permissionIds->keys()->all()
                : $roleData['permissions'];

            $role->permissions()->sync($permissionIds->only($slugs)->values()->all());
        }

        $roleIds = Role::query()->pluck('id', 'slug');

        $accounts = [
            ['name' => 'Administrator', 'email' => 'admin@sekolah.test', 'role' => 'admin'],
            ['name' => 'Operator Pesanan', 'email' => 'operator@sekolah.test', 'role' => 'operator_pesanan'],
            ['name' => 'Petugas Gudang', 'email' => 'gudang@sekolah.test', 'role' => 'gudang'],
            ['name' => 'Bagian Keuangan', 'email' => 'keuangan@sekolah.test', 'role' => 'keuangan'],
            ['name' => 'Sales PIC', 'email' => 'sales@sekolah.test', 'role' => 'sales'],
            ['name' => 'Pimpinan', 'email' => 'pimpinan@sekolah.test', 'role' => 'pimpinan'],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->roles()->sync([$roleIds[$account['role']]]);
        }
    }
}
