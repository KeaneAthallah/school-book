<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rekap rebate sekolah.
 *
 * Rebate berasal dari schools.rebate_percent yang otomatis dipotong saat
 * membuat nota pesanan, sehingga halaman ini hanya menyajikan rekapan dari
 * order (orders.discount_percent / discount_amount).
 */
class RebateController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($this->userCan($request, 'rebates.view'), 403);

        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $schoolId = $request->integer('school_id');

        $orders = Order::query()
            ->with(['school:id,name', 'invoices:id,order_id,number,status,paid_amount,total_amount'])
            ->where('discount_percent', '>', 0)
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('order_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('order_date', '<=', $dateTo))
            ->when($schoolId > 0, fn (Builder $query) => $query->where('school_id', $schoolId))
            ->orderBy('school_id')
            ->orderByDesc('order_date')
            ->get();

        $groups = $orders->groupBy('school_id')->map(function ($items): array {
            $school = $items->first()->school;

            return [
                'school_id' => $school?->getKey(),
                'school' => $school?->name,
                'rebate_percent' => $items->sortByDesc('order_date')->first()->discount_percent,
                'order_count' => $items->count(),
                'total_subtotal' => $items->sum('subtotal'),
                'total_discount' => $items->sum('discount_amount'),
                'orders' => $items->map(fn (Order $order): array => [
                    'number' => $order->number,
                    'date' => $order->order_date->toDateString(),
                    'subtotal' => $order->subtotal,
                    'discount_percent' => $order->discount_percent,
                    'discount_amount' => $order->discount_amount,
                    'invoices' => $order->invoices->map(fn ($invoice): array => [
                        'number' => $invoice->number,
                        'status' => $invoice->status->value,
                        'paid_amount' => $invoice->paid_amount,
                        'total_amount' => $invoice->total_amount,
                    ])->all(),
                    'status' => $order->status->value,
                ])->all(),
            ];
        })->values()->all();

        return view('finance.rebates.index', [
            'groups' => $groups,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'school_id' => $schoolId,
            ],
            'schools' => School::query()->active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (School $school): array => ['value' => $school->getKey(), 'label' => $school->name])
                ->all(),
        ]);
    }
}
