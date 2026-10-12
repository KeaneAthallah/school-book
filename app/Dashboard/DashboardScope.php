<?php

namespace App\Dashboard;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picking;
use App\Models\Rebate;
use App\Models\School;
use App\Models\Shipment;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Server-side record scope for a workspace.
 *
 * Every query a role dashboard runs must pass through this object so a
 * restricted role cannot observe another user's schools, orders or money by
 * editing a URL or request parameter.
 *
 * Sales / PIC is the only restricted role: it sees exactly the schools whose
 * `sales_user_id` is its own. All other roles are unrestricted here because the
 * role's permissions already gate which modules it may open at all.
 */
final readonly class DashboardScope
{
    private function __construct(
        public ?int $ownerId,
    ) {}

    public static function forRole(DashboardRole $role, User $user): self
    {
        return new self($role->isScopedToOwner() ? (int) $user->getKey() : null);
    }

    public static function unrestricted(): self
    {
        return new self(null);
    }

    /**
     * Whether results are limited to records belonging to one user.
     */
    public function isRestricted(): bool
    {
        return $this->ownerId !== null;
    }

    /**
     * @param  Builder<School>  $query
     * @return Builder<School>
     */
    public function schools(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->where('sales_user_id', $this->ownerId);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function orders(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereIn('school_id', $this->ownedSchoolIds());
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function invoices(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereIn('school_id', $this->ownedSchoolIds());
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function payments(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereIn('school_id', $this->ownedSchoolIds());
    }

    /**
     * @param  Builder<Rebate>  $query
     * @return Builder<Rebate>
     */
    public function rebates(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereIn('school_id', $this->ownedSchoolIds());
    }

    /**
     * Shipments and pickings reach the school through their order.
     *
     * @param  Builder<Shipment>  $query
     * @return Builder<Shipment>
     */
    public function shipments(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereHas('order', fn (Builder $order) => $order->whereIn('school_id', $this->ownedSchoolIds()));
    }

    /**
     * @param  Builder<Picking>  $query
     * @return Builder<Picking>
     */
    public function pickings(Builder $query): Builder
    {
        if ($this->ownerId === null) {
            return $query;
        }

        return $query->whereHas('order', fn (Builder $order) => $order->whereIn('school_id', $this->ownedSchoolIds()));
    }

    /**
     * Subquery selecting the primary keys of the schools owned by this user.
     * Using a subquery keeps the restriction inside a single SQL statement.
     */
    private function ownedSchoolIds(): Closure
    {
        return fn (QueryBuilder $query) => $query
            ->select('id')
            ->from('schools')
            ->where('sales_user_id', $this->ownerId);
    }
}
