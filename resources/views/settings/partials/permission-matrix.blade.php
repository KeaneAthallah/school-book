{{--
    Permission checkbox matrix.

    Included (not a component) on purpose: when editable it must inherit the
    surrounding form's Alpine scope, so `selected`, `toggleAll`, `allOn` and
    `groupCount` come from the parent `permissionPicker` component.

    Expects: $permissionGroups, $selectedIds, $disabled
--}}
<div class="space-y-3">
    @foreach ($permissionGroups as $group)
        @php($groupIds = array_map(static fn (array $permission): int => $permission['id'], $group['permissions']))

        <div class="rounded-card border border-line bg-panel">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-3 py-2">
                <div class="flex items-center gap-2">
                    <p class="text-[11px] font-semibold tracking-wide text-ink uppercase">{{ $group['group'] }}</p>
                    <span class="rounded-full bg-canvas px-1.5 py-0.5 text-[10px] font-medium tabular-nums text-ink-soft">
                        @if ($disabled)
                            {{ count(array_intersect($groupIds, $selectedIds)) }}/{{ count($groupIds) }}
                        @else
                            <span x-text="groupCount(@js($groupIds)) + '/{{ count($groupIds) }}'"></span>
                        @endif
                    </span>
                </div>

                @unless ($disabled)
                    <button
                        type="button"
                        class="shrink-0 text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400"
                        x-on:click="toggleAll(@js($groupIds))"
                        x-text="allOn(@js($groupIds)) ? 'Kosongkan' : 'Pilih semua'"
                    ></button>
                @endunless
            </div>

            <div class="grid gap-1.5 px-3 py-2.5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($group['permissions'] as $permission)
                    <label class="flex items-start gap-2 text-sm text-ink-soft">
                        <input
                            type="checkbox"
                            value="{{ $permission['id'] }}"
                            @checked(in_array($permission['id'], $selectedIds))
                            @if ($disabled)
                                disabled
                            @else
                                name="permissions[]"
                                x-model.number="selected"
                            @endif
                            class="mt-0.5 rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500 disabled:opacity-60"
                        />
                        <span class="min-w-0">{{ $permission['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
