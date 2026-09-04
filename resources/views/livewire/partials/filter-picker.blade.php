{{--
    A Select2 multi-picker for a Livewire property.

    Select2 rewrites the DOM around the <select>, which Livewire would then
    fight over, so the wrapper is wire:ignore and the value is pushed back with
    $wire.set on change. The wire:key ties the widget to the field it picks for:
    choosing a different field replaces the element, which re-runs x-init and
    rebuilds the picker with that field's options.

    @param array  $options   value => label
    @param mixed  $selected  currently picked value(s)
    @param string $model     the Livewire property to write (e.g. "filterValues.0")
    @param string $pickerKey unique per field/filter
--}}
<div wire:ignore wire:key="{{ $pickerKey }}" x-data x-init="
    $nextTick(() => {
        if (! window.jQuery || ! window.jQuery.fn.select2) {
            return;
        }

        const $select = window.jQuery($el).find('select');

        $select.select2({
            width: '100%',
            placeholder: @js($placeholder ?? 'Select one or more'),
            closeOnSelect: false,
        });

        $select.on('change', () => $wire.set(@js($model), $select.val() ?? []));
    })
">
    @php $picked = array_map('strval', (array) ($selected ?? [])); @endphp
    <select multiple class="form-select">
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected(in_array((string) $value, $picked, true))>{{ $label }}</option>
        @endforeach
    </select>
</div>
