<div>
    <form wire:submit.prevent="generateReport">
        <label>Select Table:</label><br>
        <select id="table" wire:model="selectedTable">
            <option value="">-- Select Table --</option>
            @foreach ($tables as $table)
                <option value="{{ array_values((array) $table)[0] }}">
                    {{ array_values((array) $table)[0] }}
                </option>
            @endforeach
        </select>

        <br><br>

        @if ($columns)
            <label>Select Columns:</label><br>
            @foreach ($columns as $column)
                <input type="checkbox" wire:model="selectedColumns" value="{{ $column->Field }}"> {{ $column->Field }}<br>
            @endforeach
        @endif

        <br><button type="submit">Generate Report</button>
    </form>
</div>
@script
    <script>
        $("#table").change(function() {
            var data = $('#table').select("val");
            console.log(data);
            
            @this.set('selectedTable', data);
        })
    </script>
@endscript
