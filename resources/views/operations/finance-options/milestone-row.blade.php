{{-- One editable milestone row. Rendered for every saved row, and once more
     with $index = '__INDEX__' inside a <template> that the "New Milestone"
     button clones. The script renumbers the names on every add/remove, so the
     posted rows always come back as milestones[0..n]. --}}
@php
    $amountType = $row['amount_type'] ?? 'percent';
    $isActive = (bool) ($row['is_active'] ?? true);
@endphp
<tr class="milestone-row {{ $isActive ? '' : 'is-inactive' }}">
    <td class="ms-col-num"><span class="ms-row-number"></span></td>
    <td>
        <input type="hidden" data-field="id" name="milestones[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
        <input type="text" class="form-control" data-field="label"
            name="milestones[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}"
            placeholder="Milestone name" aria-label="Milestone name" required>
        @error("milestones.$index.label")
            <div class="ms-error">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select" data-field="trigger" name="milestones[{{ $index }}][trigger]" aria-label="Trigger" required>
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
            <div class="ms-error">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select milestone-amount-type" data-field="amount_type"
            name="milestones[{{ $index }}][amount_type]" aria-label="Amount type" required>
            @foreach ($milestoneAmountTypes as $value => $label)
                <option value="{{ $value }}" @selected($amountType === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error("milestones.$index.amount_type")
            <div class="ms-error">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <div class="ms-amount is-{{ $amountType }}">
            <span class="ms-unit ms-unit-prefix">$</span>
            <input type="number" step="0.01" min="0" class="form-control milestone-amount-value"
                data-field="amount_value" name="milestones[{{ $index }}][amount_value]"
                value="{{ $row['amount_value'] ?? '' }}" aria-label="Amount">
            <span class="ms-unit ms-unit-suffix">%</span>
        </div>
        @error("milestones.$index.amount_value")
            <div class="ms-error">{{ $message }}</div>
        @enderror
    </td>
    <td>
        <select class="form-select milestone-amount-base" data-field="amount_base" name="milestones[{{ $index }}][amount_base]" aria-label="Base" required>
            @foreach ($milestoneBases as $value => $label)
                <option value="{{ $value }}" @selected(($row['amount_base'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </td>
    <td>
        {{-- The last option opens the "New Payee" dialog instead of being picked. --}}
        <select class="form-select milestone-payee" data-field="payee" name="milestones[{{ $index }}][payee]" aria-label="Payee" required>
            {{-- Active payees, plus this row's own if it was switched off since. --}}
            @foreach (\App\Models\AccountTransaction::payeeChoices($row['payee'] ?? 'customer') as $value => $label)
                <option value="{{ $value }}" @selected(($row['payee'] ?? 'customer') === $value)>{{ $label }}</option>
            @endforeach
            <option value="__new__">+ Add new payee…</option>
        </select>
        @error("milestones.$index.payee")
            <div class="ms-error">{{ $message }}</div>
        @enderror
    </td>
    <td>
        {{-- The hidden 0 is posted when the switch is off; checked, the 1 after it wins. --}}
        <input type="hidden" data-field="is_active" name="milestones[{{ $index }}][is_active]" value="0">
        <label class="ms-toggle">
            <input type="checkbox" class="milestone-active" data-field="is_active"
                name="milestones[{{ $index }}][is_active]" value="1" @checked($isActive)>
            <span class="ms-toggle-track"><span class="ms-toggle-thumb"></span></span>
            <span class="ms-toggle-label">{{ $isActive ? 'Active' : 'Inactive' }}</span>
        </label>
    </td>
    <td class="ms-col-action">
        <button type="button" class="ms-icon-btn milestone-remove" title="Remove milestone" aria-label="Remove milestone">
            <i class="icofont-trash"></i>
        </button>
    </td>
</tr>
