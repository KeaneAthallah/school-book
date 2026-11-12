@php
    $status = $row['payment_status'] ?? '';
    $tones = [
        'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
        'partial' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
        'unpaid' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        'no_invoice' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
    ];
    $labels = [
        'paid' => 'Lunas',
        'partial' => 'Sebagian',
        'unpaid' => 'Belum Bayar',
        'no_invoice' => 'Belum Ada Invoice',
    ];
    $tone = $tones[$status] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300';
    $label = $labels[$status] ?? $status;
@endphp
<span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap {{ $tone }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true"></span>
    {{ $label }}
</span>