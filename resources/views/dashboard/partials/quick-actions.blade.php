{{--
    Permission-filtered quick actions.

    Every entry is checked against the signed-in user's permissions before it is
    rendered, so the panel never offers a link that would answer 403 - and never
    links to a route that does not exist.
    Expects: $actions = [['label', 'icon', 'route', 'params'?, 'permission'?]]
--}}
@php
    use App\Support\Accent;

    $user = auth()->user();
    $visible = array_values(array_filter(
        $actions,
        fn (array $action): bool => ! isset($action['permission'])
            || ($user !== null && $user->hasPermission($action['permission'])),
    ));
@endphp

@if ($visible !== [])
    <x-card class="mt-4">
        <x-card-header title="Aksi Cepat" icon="bolt" />

        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($visible as $action)
                <a
                    href="{{ route($action['route'], $action['params'] ?? []) }}"
                    class="flex items-center gap-2.5 rounded-control border border-line px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:border-line-strong hover:bg-panel-soft hover:text-ink"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-control {{ Accent::pastelFor($action['icon']) }}">
                        <x-icon :name="$action['icon']" class="h-4 w-4" />
                    </span>
                    <span class="min-w-0 truncate">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </x-card>
@endif
