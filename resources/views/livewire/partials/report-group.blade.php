{{--
    One group in a report preview: its header, then either its own detail rows
    or the groups nested inside it, then its subtotal. The same partial serves
    both levels, so a second grouping level needs no second layout.
--}}
<tr class="rb-group-row">
    <td colspan="{{ count($reportColumns) }}" style="padding-left: {{ 14 + $depth * 18 }}px;">
        <span class="fw-bold">{{ $this->groupHeading($group['value'], $depth) }}</span>
        <span class="ms-2">{{ number_format($group['count']) }}
            {{ Str::plural('record', $group['count']) }}</span>
    </td>
</tr>

@if (!empty($group['children']))
    @foreach ($group['children'] as $child)
        @include('livewire.partials.report-group', ['group' => $child, 'depth' => $depth + 1])
    @endforeach
@else
    @foreach ($group['rows'] as $row)
        @include('livewire.partials.report-row', ['row' => $row])
    @endforeach
@endif

<tr class="rb-subtotal-row">
    @foreach ($reportColumns as $index => $column)
        <td class="{{ ($column['numeric'] ?? false) ? 'rb-num' : '' }}"
            @if ($index === 0) style="padding-left: {{ 14 + $depth * 18 }}px;" @endif>
            @if ($index === 0)
                Subtotal · {{ $this->groupLabelFor($group['value']) }}
            @elseif (isset($group['aggregates'][$column['field']]))
                {{ $this->formatSummary($group['aggregates'][$column['field']]) }}
            @endif
        </td>
    @endforeach
</tr>
