{{-- One editable milestone row. Rendered for every saved row, and once more
     with $index = '__INDEX__' inside a <template> that the "Add Milestone"
     button clones. The script renumbers the names on every add/remove, so the
     posted rows always come back as milestones[0..n]. --}}
<tr class="milestone-row">
    <td>
        <input type="hidden" data-field="id" name="milestones[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
        <input type="text" class="form-control form-control-sm" data-field="label"
            name="milestones[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="Milestone name" required>
        @error("milestones.$index.label")
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select form-select-sm" data-field="trigger" name="milestones[{{ $index }}][trigger]" required>
            @if (!empty($row['trigger']) && !collect($milestoneTriggers)->contains(fn ($group) => array_key_exists($row['trigger'], $group)))
                <option value="{{ $row['trigger'] }}" selected>Unavailable trigger - choose another</option>
            @endif
            @foreach ($milestoneTriggers as $group => $options)
                <optgroup label="{{ $group }}">
                    @foreach ($options as $value => $label)
                        <option value="{{ $value }}" @selected(($row['trigger'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error("milestones.$index.trigger")
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select form-select-sm milestone-amount-type" data-field="amount_type"
            name="milestones[{{ $index }}][amount_type]" required>
            @foreach ($milestoneAmountTypes as $value => $label)
                <option value="{{ $value }}" @selected(($row['amount_type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error("milestones.$index.amount_type")
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <input type="number" step="0.01" min="0" class="form-control form-control-sm milestone-amount-value"
            data-field="amount_value" name="milestones[{{ $index }}][amount_value]"
            value="{{ $row['amount_value'] ?? '' }}" placeholder="$ or %">
        @error("milestones.$index.amount_value")
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select form-select-sm" data-field="amount_base" name="milestones[{{ $index }}][amount_base]" required>
            @foreach ($milestoneBases as $value => $label)
                <option value="{{ $value }}" @selected(($row['amount_base'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" data-field="payee" name="milestones[{{ $index }}][payee]" required>
            @foreach ($milestonePayees as $value => $label)
                <option value="{{ $value }}" @selected(($row['payee'] ?? 'customer') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select form-select-sm" data-field="is_active" name="milestones[{{ $index }}][is_active]" required>
            <option value="1" @selected((bool) ($row['is_active'] ?? true))>Active</option>
            <option value="0" @selected(!(bool) ($row['is_active'] ?? true))>Inactive</option>
        </select>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger milestone-remove" title="Remove milestone">
            <i class="icofont-trash"></i>
        </button>
    </td>
</tr>
