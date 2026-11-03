{{--
    A Select2 picker for a Livewire property - several values by default, or
    one with 'multiple' => false. Either way it can be searched, which is what
    a long list needs.

    Select2 rewrites the DOM around the <select>, which Livewire would then
    fight over, so the wrapper is wire:ignore and the value is pushed back with
    $wire.set on change. The wire:key ties the widget to the field it picks for:
    choosing a different field replaces the element, which re-runs x-init and
    rebuilds the picker with that field's options.

    @param array  $options   value => label, or group label => [value => label]
    @param mixed  $selected  currently picked value(s)
    @param string $model     the Livewire property to write (e.g. "filterValues.0")
    @param string $pickerKey unique per field/filter
    @param bool   $multiple  optional, default true
    @param bool   $clearable optional, a single pick that can be emptied (default true)
--}}
@php
    $multiple = $multiple ?? true;
    $clearable = ! $multiple && ($clearable ?? true);
    $picked = array_map('strval', (array) ($selected ?? []));
@endphp
<div wire:ignore wire:key="{{ $pickerKey }}" x-data x-init="
    $nextTick(() => {
        if (! window.jQuery || ! window.jQuery.fn.select2) {
            return;
        }

        const multiple = @js($multiple);
        const clearable = @js($clearable);
        const $select = window.jQuery($el).find('select');

        $select.select2({
            width: '100%',
            placeholder: @js($placeholder ?? ($multiple ? 'Select one or more' : 'Select one')),
            closeOnSelect: ! multiple,
            allowClear: clearable,
        });

        $select.on('change', () => $wire.set(@js($model), $select.val() ?? (multiple ? [] : '')));
    })
">
    <select @if ($multiple) multiple @endif class="form-select">
        @if ($clearable)
            <option value=""></option>
        @endif
        @foreach ($options as $value => $label)
            @if (is_array($label))
                <optgroup label="{{ $value }}">
                    @foreach ($label as $groupValue => $groupLabel)
                        <option value="{{ $groupValue }}" @selected(in_array((string) $groupValue, $picked, true))>{{ $groupLabel }}</option>
                    @endforeach
                </optgroup>
            @else
                <option value="{{ $value }}" @selected(in_array((string) $value, $picked, true))>{{ $label }}</option>
            @endif
        @endforeach
    </select>
</div>
