@props([
    'columns' => [],
    'rows' => [],
    'empty' => 'Tidak ada data.',
    'emptyIcon' => 'clipboard',
])

@php
    $alignClass = fn (array $column): string => match ($column['align'] ?? 'left') {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<div {{ $attributes->merge(['class' => 'scroll-slim overflow-x-auto']) }}>
    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="border-b border-line bg-panel-soft text-xs font-semibold tracking-wide text-ink-soft uppercase">
                @foreach ($columns as $column)
                    <th scope="col" class="px-3 py-2.5 whitespace-nowrap {{ $alignClass($column) }} {{ $column['class'] ?? '' }}">
                        {{ $column['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b border-line transition-colors last:border-0 hover:bg-panel-soft">
                    @foreach ($columns as $column)
                        @php
                            $value = data_get($row, $column['key'] ?? '');
                        @endphp
                        <td class="px-3 py-2.5 align-middle {{ $alignClass($column) }} {{ $column['class'] ?? '' }}">
                            @if (isset($column['view']))
                                @include($column['view'], ['row' => $row, 'value' => $value, 'column' => $column])
                            @elseif (isset($column['link']))
                                <a
                                    href="{{ route($column['link'], data_get($row, $column['linkKey'] ?? 'id')) }}"
                                    class="font-medium text-ink transition hover:text-brand-600 hover:underline dark:hover:text-brand-300"
                                >{{ $value }}</a>
                            @else
                                <x-cell :value="$value" :type="$column['type'] ?? 'text'" />
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">
                        <x-empty-state :message="$empty" :icon="$emptyIcon" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
