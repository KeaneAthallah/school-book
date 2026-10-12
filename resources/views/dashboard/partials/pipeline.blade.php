{{--
    Fulfillment pipeline. Expects: $steps
--}}
<ol class="flex flex-col gap-3 p-4 sm:flex-row sm:items-stretch sm:gap-2">
    @foreach ($steps as $step)
        <li class="relative flex flex-1 items-center gap-3 sm:flex-col sm:gap-2">
            <div class="flex w-full items-center sm:justify-center">
                @if (! $loop->first)
                    <span class="mr-3 hidden h-0.5 flex-1 bg-line sm:block" aria-hidden="true"></span>
                @endif
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $step['tone'] }}">
                    <x-icon :name="$step['icon']" class="h-5 w-5" />
                </span>
                @if (! $loop->last)
                    <span class="ml-3 h-0.5 flex-1 bg-line sm:hidden" aria-hidden="true"></span>
                @endif
            </div>
            <div class="text-left sm:w-full sm:text-center">
                <p class="text-lg font-semibold text-ink tabular-nums">{{ \App\Support\Format::number($step['value']) }}</p>
                <p class="text-xs text-ink-soft">{{ $step['label'] }}</p>
            </div>
        </li>
    @endforeach
</ol>
