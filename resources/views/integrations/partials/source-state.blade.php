
@if ($row['enabled'] ?? false)
    <x-status-badge value="active" label="Terkonfigurasi" :dot="false" />
@else
    <x-status-badge value="inactive" label="Belum dikonfigurasi" :dot="false" />
@endif
