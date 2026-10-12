<?php

namespace App\Dashboard;

use App\Models\User;

/**
 * Resolves the workspace for an authenticated user from server-side state only.
 *
 * The resolution never reads request input: it is derived exclusively from the
 * user's persisted roles, so a caller cannot influence the result by changing a
 * URL, a query string, a posted field or front-end state.
 *
 * A user may hold several roles. The precedence list below encodes which
 * workspace wins in that case and is the single documented policy:
 *
 *   1. Admin              - full administrative responsibility
 *   2. Operator Pesanan   - order intake owns the order pipeline
 *   3. Gudang             - warehouse execution
 *   4. Keuangan           - financial follow-up
 *   5. Sales / PIC        - per-user school portfolio
 *   6. Pimpinan           - read-only executive view
 *
 * A user with no recognised role resolves to null, which the controller renders
 * as a deliberately data-free "limited access" page.
 */
class DashboardRoleResolver
{
    /**
     * @var list<DashboardRole>
     */
    private const PRECEDENCE = [
        DashboardRole::Admin,
        DashboardRole::Operator,
        DashboardRole::Warehouse,
        DashboardRole::Finance,
        DashboardRole::Sales,
        DashboardRole::Executive,
    ];

    public function resolve(User $user): ?DashboardRole
    {
        $slugs = $this->roleSlugs($user);

        if ($slugs === []) {
            return null;
        }

        foreach (self::PRECEDENCE as $role) {
            if (array_intersect($role->roleSlugs(), $slugs) !== []) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function roleSlugs(User $user): array
    {
        return array_values($user->roles()
            ->pluck('slug')
            ->map(fn (mixed $slug): string => (string) $slug)
            ->all());
    }
}
