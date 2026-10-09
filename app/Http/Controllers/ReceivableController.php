<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'invoices.view'), 403);

        $user = $request->user();
        $search = $request->string('search')->toString();
        $bucket = $request->string('bucket')->toString();

        $base = fn (): Builder => Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->when(
                $user->hasRole('sales') && ! $user->isAdmin(),
                fn (Builder $query) => $query->whereHas('school', fn (Builder $school) => $school->where('sales_user_id', $user->getKey())),
            );

        $invoices = $base()
            ->with('school:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                $inner->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Invoice $invoice): array => [
                'id' => $invoice->getKey(),
                'number' => $invoice->number,
                'school' => $invoice->school?->name,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'total' => $invoice->total_amount,
                'paid' => $invoice->paid_amount,
                'outstanding' => $invoice->outstandingAmount(),
                'days_overdue' => $this->daysOverdue($invoice),
                'state' => $this->state($invoice),
                'payment_status' => $invoice->paymentStatus()->value,
                'payment_status_label' => $invoice->paymentStatus()->label(),
            ]);

        if ($bucket !== '') {
            $invoices->setCollection(
                $invoices->getCollection()
                    ->filter(fn (array $row): bool => $row['state'] === $bucket)
                    ->values(),
            );
        }

        $aging = $this->aging($base());

        return view('finance.receivables.index', [
            'invoices' => $invoices,
            'filters' => ['search' => $search, 'bucket' => $bucket],
            'summary' => [
                'total' => Money::add((string) $base()->sum('total_amount'), '0'),
                'paid' => Money::add((string) $base()->sum('paid_amount'), '0'),
                'outstanding' => Money::sub(
                    (string) $base()->sum('total_amount'),
                    (string) $base()->sum('paid_amount'),
                ),
                'overdue_count' => $base()->whereDate('due_date', '<', now()->toDateString())->count(),
                'overdue_amount' => Money::sub(
                    (string) $base()->whereDate('due_date', '<', now()->toDateString())->sum('total_amount'),
                    (string) $base()->whereDate('due_date', '<', now()->toDateString())->sum('paid_amount'),
                ),
            ],
            'aging' => $aging,
        ]);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return array<string, string>
     */
    private function aging(Builder $query): array
    {
        $buckets = ['current' => '0.00', '1_30' => '0.00', '31_60' => '0.00', 'over_60' => '0.00'];
        $today = Carbon::today();

        foreach ($query->get(['due_date', 'total_amount', 'paid_amount']) as $invoice) {
            $due = Carbon::parse((string) $invoice->getAttribute('due_date'));
            $days = (int) $due->diffInDays($today, false);
            $outstanding = Money::sub((string) $invoice->getAttribute('total_amount'), (string) $invoice->getAttribute('paid_amount'));

            $key = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => '1_30',
                $days <= 60 => '31_60',
                default => 'over_60',
            };

            $buckets[$key] = Money::add($buckets[$key], $outstanding);
        }

        return $buckets;
    }

    private function daysOverdue(Invoice $invoice): int
    {
        return $invoice->due_date->lessThan(now()->startOfDay())
            ? (int) $invoice->due_date->diffInDays(now()->startOfDay())
            : 0;
    }

    private function state(Invoice $invoice): string
    {
        if ($invoice->due_date->lessThan(now()->startOfDay())) {
            return 'overdue';
        }

        return Money::compare($invoice->paid_amount, '0') > 0 ? 'partial' : 'unpaid';
    }
}
