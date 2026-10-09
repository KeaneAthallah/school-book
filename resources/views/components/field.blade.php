@props([
    'label',
    'name' => null,
    'required' => false,
    'hint' => null,
    'class' => '',
])

<label class="block space-y-1.5 {{ $class }}">
    <span class="block text-xs font-medium text-ink-soft">
        {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
    </span>

    {{ $slot }}

    @if ($hint)
        <span class="block text-xs text-ink-muted">{{ $hint }}</span>
    @endif

    @if ($name && $errors->has($name))
        <span class="block text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $errors->first($name) }}</span>
    @endif
</label>
