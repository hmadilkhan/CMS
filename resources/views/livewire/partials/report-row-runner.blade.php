{{-- One detail row of a run report; the runner's own cell rules, shared by the
     grouped and flat paths so they cannot drift apart. --}}
<tr>
    @foreach ($reportColumns as $column)
        @php
            $value =
                $column['type'] === 'calculated'
                    ? $row->{$column['field']} ?? 'N/A'
                    : $this->getNestedProperty($row, $column['field']);
            $numeric = is_numeric($value) && !is_string($value);
            if ($numeric) {
                $value = number_format($value, is_float($value + 0) && floor($value + 0) != $value + 0 ? 2 : 0);
            }
        @endphp
        <td class="{{ $numeric || ($column['numeric'] ?? false) ? 'rr-num' : '' }}">
            <div style="max-width: 320px; word-wrap: break-word;">
                {{ $value === null || $value === '' ? '—' : $value }}
            </div>
        </td>
    @endforeach
</tr>
