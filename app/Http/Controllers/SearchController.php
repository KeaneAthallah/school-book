<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Permission-scoped global search used by the top navigation.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return response()->json(['groups' => []]);
        }

        $user = $request->user();
        $like = '%'.$term.'%';
        $groups = [];

        if ($user->hasPermission('orders.view')) {
            $orders = $this->scopedOrders($user)
                ->with('school:id,name')
                ->where(function (Builder $query) use ($like): void {
                    $query->where('number', 'like', $like)
                        ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', $like));
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $this->addGroup($groups, 'orders', 'Pesanan', $orders->map(fn (Order $order): array => [
                'title' => $order->number,
                'subtitle' => ($order->school->name).' · '.$order->status->label(),
                'href' => '/orders/'.$order->getKey(),
            ])->all());
        }

        if ($user->hasPermission('invoices.view')) {
            $invoices = Invoice::query()
                ->with('school:id,name')
                ->when(
                    $user->hasRole('sales') && ! $user->isAdmin(),
                    fn (Builder $query) => $query->whereHas('school', fn (Builder $school) => $school->where('sales_user_id', $user->getKey())),
                )
                ->where(function (Builder $query) use ($like): void {
                    $query->where('number', 'like', $like)
                        ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', $like));
                })
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            $this->addGroup($groups, 'invoices', 'Invoice', $invoices->map(fn (Invoice $invoice): array => [
                'title' => $invoice->number,
                'subtitle' => ($invoice->school->name).' · '.$invoice->paymentStatus()->label(),
                'href' => '/invoices/'.$invoice->getKey(),
            ])->all());
        }

        if ($user->hasPermission('schools.view')) {
            $schools = School::query()
                ->when(
                    $user->hasRole('sales') && ! $user->isAdmin(),
                    fn (Builder $query) => $query->where('sales_user_id', $user->getKey()),
                )
                ->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('npsn', 'like', $like))
                ->orderBy('name')
                ->limit(5)
                ->get();

            $this->addGroup($groups, 'schools', 'Sekolah', $schools->map(fn (School $school): array => [
                'title' => $school->name,
                'subtitle' => $school->npsn !== null ? 'NPSN '.$school->npsn : null,
                'href' => '/master/schools?search='.urlencode($school->name),
            ])->all());
        }

        if ($user->hasPermission('books.view')) {
            $books = Book::query()
                ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('code', 'like', $like))
                ->orderBy('title')
                ->limit(5)
                ->get();

            $this->addGroup($groups, 'books', 'Buku', $books->map(fn (Book $book): array => [
                'title' => $book->title,
                'subtitle' => 'Kode '.$book->code,
                'href' => '/master/books?search='.urlencode($book->code),
            ])->all());
        }

        return response()->json(['groups' => $groups]);
    }

    /**
     * @return Builder<Order>
     */
    private function scopedOrders(User $user): Builder
    {
        return Order::query()->when(
            $user->hasRole('sales') && ! $user->isAdmin(),
            fn (Builder $query) => $query->whereHas('school', fn (Builder $school) => $school->where('sales_user_id', $user->getKey())),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  array<int, array<string, mixed>>  $items
     */
    private function addGroup(array &$groups, string $key, string $label, array $items): void
    {
        if ($items !== []) {
            $groups[] = ['key' => $key, 'label' => $label, 'items' => $items];
        }
    }
}
