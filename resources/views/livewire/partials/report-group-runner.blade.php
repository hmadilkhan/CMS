{{--
    One group of a run report: header, then the groups nested inside it or its
    own rows, then its subtotal. One partial for both levels.
--}}
<tr class="rr-group-row">
    <td colspan="{{ count($reportColumns) }}" style="padding-left: {{ 14 + $depth * 18 }}px;">
        {{ $this->groupHeading($group['value'], $depth) }}
        <span class="fw-normal ms-2">{{ number_format($group['count']) }}
            {{ Str::plural('record', $group['count']) }}</span>
    </td>
</tr>

@if (!empty($group['children']))
    @foreach ($group['children'] as $child)
        @include('livewire.partials.report-group-runner', ['group' => $child, 'depth' => $depth + 1])
    @endforeach
@else
    @foreach ($group['rows'] as $row)
        @include('livewire.partials.report-row-runner', ['row' => $row])
    @endforeach
@endif

<tr class="rr-subtotal-row">
    @foreach ($reportColumns as $index => $column)
        <td class="{{ ($column['numeric'] ?? false) ? 'rr-num' : '' }}"
            @if ($index === 0) style="padding-left: {{ 14 + $depth * 18 }}px;" @endif>
            @if ($index === 0)
                Subtotal · {{ $this->groupLabelFor($group['value']) }}
            @elseif (isset($group['aggregates'][$column['field']]))
                {{ $this->formatSummary($group['aggregates'][$column['field']]) }}
            @endif
        </td>
    @endforeach
</tr>
