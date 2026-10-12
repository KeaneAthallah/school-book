{{--
    Period filter shared by every role dashboard.
    Expects: $period
--}}
<form method="GET" action="{{ route('dashboard') }}" class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
    <x-field label="Dari" class="w-full min-[420px]:w-36">
        <x-input type="date" name="date_from" value="{{ $period->start->toDateString() }}" aria-label="Tanggal awal" />
    </x-field>
    <x-field label="Sampai" class="w-full min-[420px]:w-36">
        <x-input type="date" name="date_to" value="{{ $period->end->toDateString() }}" aria-label="Tanggal akhir" />
    </x-field>
    <x-button type="submit" variant="secondary">
        <x-icon name="filter" class="h-4 w-4" />
        Terapkan
    </x-button>
    <x-button
        :href="route('dashboard', ['date_from' => now()->subDays(6)->toDateString(), 'date_to' => now()->toDateString()])"
        variant="ghost"
        size="sm"
    >7 hari</x-button>
    <x-button
        :href="route('dashboard', ['date_from' => now()->subDays(29)->toDateString(), 'date_to' => now()->toDateString()])"
        variant="ghost"
        size="sm"
    >30 hari</x-button>
</form>
