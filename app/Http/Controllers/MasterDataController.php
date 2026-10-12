<?php

namespace App\Http\Controllers;

use App\Support\MasterDataRegistry;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request, string $resource): View
    {
        $definition = $this->definition($resource);
        $this->authorizeView($request, $definition);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];

        $query = $this->scoped($definition, $modelClass);

        /** @var list<string> $with */
        $with = $definition['with'];
        if ($with !== []) {
            $query->with($with);
        }

        /** @var list<array{0: string, 1: string, 2: string, 3: string}> $joins */
        $joins = $definition['joins'];
        foreach ($joins as [$table, $first, $operator, $second]) {
            $query->join($table, $first, $operator, $second);
        }

        // A bare join would let the products columns shadow the child table's
        // own columns (notably id), so keep the query rooted on its model.
        if ($joins !== []) {
            $query->select($query->getModel()->getTable().'.*');
        }

        $search = $request->string('search')->toString();

        /** @var list<string> $searchColumns */
        $searchColumns = $definition['search'];

        if ($search !== '' && $searchColumns !== []) {
            $query->where(function (Builder $builder) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $items = $query
            ->orderBy((string) $definition['sort'])
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Model $record): array => [
                'id' => $record->getKey(),
                'display' => $this->display($record, $definition),
                'editable' => $this->editable($record, $definition),
            ]);

        return view('master.resource-index', [
            'resource' => $this->resourcePayload($resource, $definition),
            'items' => $items,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Request $request, string $resource): View
    {
        $definition = $this->definition($resource);
        $this->authorizeManage($request, $definition);

        return view('master.resource-form', [
            'resource' => $this->resourcePayload($resource, $definition),
            'record' => null,
            'values' => [],
            'title' => 'Tambah '.$definition['title'],
        ]);
    }

    public function edit(Request $request, string $resource, int $id): View
    {
        $definition = $this->definition($resource);
        $this->authorizeManage($request, $definition);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $record = $this->scoped($definition, $modelClass)->findOrFail($id);

        $values = $definition['values'];

        return view('master.resource-form', [
            'resource' => $this->resourcePayload($resource, $definition),
            'record' => $record,
            'values' => is_callable($values) ? $values($record) : $this->editable($record, $definition),
            'title' => 'Ubah '.$definition['title'],
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeManage($request, $definition);

        /** @var array<string, mixed> $attributes */
        $attributes = $request->validate($this->rules($definition, null));

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];

        DB::transaction(function () use ($definition, $modelClass, $attributes): void {
            $save = $definition['save'];
            if (is_callable($save)) {
                /** @var callable(array<string, mixed>, null): Model $save */
                $save($attributes, null);

                return;
            }

            $model = new $modelClass;
            $attributes = $this->prepare($definition, $model, $attributes);
            $model->fill($attributes)->save();
        });

        return back()->with('success', $definition['title'].' berhasil ditambahkan.');
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeManage($request, $definition);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $record = $this->scoped($definition, $modelClass)->findOrFail($id);

        /** @var array<string, mixed> $attributes */
        $attributes = $request->validate($this->rules($definition, $record));

        DB::transaction(function () use ($definition, $record, $attributes): void {
            $save = $definition['save'];
            if (is_callable($save)) {
                /** @var callable(array<string, mixed>, Model): Model $save */
                $save($attributes, $record);

                return;
            }

            $attributes = $this->prepare($definition, $record, $attributes);
            $record->fill($attributes)->save();
        });

        return back()->with('success', $definition['title'].' berhasil diperbarui.');
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeManage($request, $definition);

        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];
        $record = $this->scoped($definition, $modelClass)->findOrFail($id);

        try {
            $record->delete();
        } catch (QueryException) { // @phpstan-ignore catch.neverThrown
            return back()->with('error', $definition['title'].' tidak dapat dihapus karena masih dipakai pada transaksi lain.');
        }

        return back()->with('success', $definition['title'].' berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $resource): array
    {
        $definition = MasterDataRegistry::get($resource);

        abort_if($definition === null, 404);

        return $definition;
    }

    /**
     * Apply the definition's optional scope so a resource only ever
     * sees its own records (e.g. books vs. barang lainnya).
     *
     * @param  array<string, mixed>  $definition
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    private function scoped(array $definition, string $modelClass): Builder
    {
        $query = $modelClass::query();

        $scope = $definition['scope'];

        if (is_callable($scope)) {
            /** @var callable(Builder<Model>): void $scope */
            $scope($query);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function rules(array $definition, ?Model $model): array
    {
        /** @var callable(?Model): array<string, mixed> $rules */
        $rules = $definition['rules'];

        return $rules($model);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepare(array $definition, Model $model, array $attributes): array
    {
        $prepare = $definition['prepare'];

        if (is_callable($prepare)) {
            /** @var callable(Model, array<string, mixed>): array<string, mixed> $prepare */
            return $prepare($model, $attributes);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function resourcePayload(string $resource, array $definition): array
    {
        $fields = $definition['fields'];

        foreach ($fields as $index => $field) {
            $options = $field['options'] ?? null;

            if (is_callable($options)) {
                /** @var callable(): array<int, array{value: mixed, label: string}> $options */
                $fields[$index]['options'] = $options();
            }
        }

        return [
            'slug' => $resource,
            'title' => $definition['title'],
            'columns' => $definition['columns'],
            'fields' => $fields,
            'can' => [
                'manage' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, string|int|float|bool|null>
     */
    private function display(Model $record, array $definition): array
    {
        /** @var list<array{key: string, label: string, type?: string}> $columns */
        $columns = $definition['columns'];
        $display = [];

        foreach ($columns as $column) {
            $value = data_get($record, $column['key']);

            if ($value instanceof BackedEnum) {
                $value = method_exists($value, 'label') ? $value->label() : $value->value;
            }

            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if (is_scalar($value) || $value === null) {
                Arr::set($display, $column['key'], $value);
            } else {
                Arr::set($display, $column['key'], (string) json_encode($value));
            }
        }

        return $display;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, string|int|float|bool|null>
     */
    private function editable(Model $record, array $definition): array
    {
        /** @var list<array{name: string}> $fields */
        $fields = $definition['fields'];
        $editable = [];

        foreach ($fields as $field) {
            $value = $record->getAttribute($field['name']);

            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if (is_scalar($value) || $value === null) {
                $editable[$field['name']] = $value;
            }
        }

        return $editable;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function authorizeView(Request $request, array $definition): void
    {
        abort_unless(
            $this->userCan($request, (string) $definition['view_permission']),
            403,
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function authorizeManage(Request $request, array $definition): void
    {
        abort_unless(
            $this->userCan($request, (string) $definition['manage_permission']),
            403,
        );
    }
}
