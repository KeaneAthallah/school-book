@props([
    'value',
    'label' => null,
    'dot' => true,
])

@php
    $tones = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        'pending' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        'unpaid' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        'inactive' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        'open' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'partial' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'partial_ready' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'partial_shipped' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'pending_approval' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'received' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'submitted' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'processing' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
        'picking' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
        'issued' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'sent' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'shipped' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'fully_shipped' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
        'ready' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'ready_to_ship' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'picked' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'packed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'confirmed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'verified' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'fulfilled' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        'overdue' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        'reversed' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
        'error' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
    ];

    $tone = $tones[$value] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300';
@endphp

<span
    role="status"
    aria-label="{{ $label ?? $value }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ' . $tone]) }}
>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    @endif
    {{ $label ?? $value }}
</span>
