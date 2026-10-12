
<div class="grid gap-3 p-4 sm:grid-cols-2">
    @foreach ($fields as $field)
        @php
            $name = $field['name'];
            $value = old($name, $values[$name] ?? null);
            $invalid = $errors->has($name);
            $wide = ($field['type'] ?? 'text') === 'textarea';
        @endphp

        <x-field
            :label="$field['label']"
            :name="$name"
            :required="$field['required'] ?? false"
            :class="$wide ? 'sm:col-span-2' : ''"
        >
            @if (($field['type'] ?? 'text') === 'select')
                <x-select :name="$name" :invalid="$invalid">
                    <option value="">— Pilih —</option>
                    @foreach ($field['options'] ?? [] as $option)
                        <option
                            value="{{ $option['value'] }}"
                            @selected((string) $value === (string) $option['value'])
                        >{{ $option['label'] }}</option>
                    @endforeach
                </x-select>
            @elseif (($field['type'] ?? 'text') === 'textarea')
                <x-textarea :name="$name" rows="3" :invalid="$invalid">{{ $value }}</x-textarea>
            @elseif (($field['type'] ?? 'text') === 'checkbox')
                <input type="hidden" name="{{ $name }}" value="0" />
                <label class="flex items-center gap-2 text-sm text-ink-soft">
                    <input
                        type="checkbox"
                        name="{{ $name }}"
                        value="1"
                        @checked((bool) $value)
                        class="rounded border-line-strong bg-panel text-brand-600 focus:ring-brand-500"
                    />
                    Ya
                </label>
            @elseif (($field['type'] ?? 'text') === 'file')
                <div x-data="photoPreview" class="space-y-1.5">
                    @if (! empty($value))
                        <span class="block">
                            <img src="{{ Storage::url((string) $value) }}" alt="{{ $field['label'] }}" loading="lazy" class="h-16 w-16 rounded-control border border-line object-cover" />
                        </span>
                    @endif
                    <input
                        type="file"
                        name="{{ $name }}"
                        accept="image/jpeg,image/png,image/webp"
                        aria-label="{{ $field['label'] }}"
                        class="w-full rounded-control border border-line bg-canvas px-2.5 py-1.5 text-xs text-ink file:mr-1.5 file:rounded-control file:border-0 file:bg-brand-600 file:px-2 file:py-1 file:text-xs file:font-medium file:text-white"
                        @change="pick($event)"
                    />
                    <img x-show="preview" :src="preview" alt="Pratinjau {{ $field['label'] }}" class="h-20 w-20 rounded-control border border-line object-cover sm:h-24 sm:w-24" />
                </div>
            @else
                @if (($field['type'] ?? 'text') === 'number')
                    <x-input
                        type="number"
                        :name="$name"
                        :value="$value"
                        :invalid="$invalid"
                        step="any"
                    />
                @else
                    <x-input
                        type="{{ $field['type'] ?? 'text' }}"
                        :name="$name"
                        :value="$value"
                        :invalid="$invalid"
                    />
                @endif
            @endif
        </x-field>
    @endforeach
</div>
