<div>
    <section>
        <div class="card">
            <div class="card-body">
                <form wire:submit="submitData">
                    <div class="row">
                        <div class="col-md-12">
                            <label>Select Columns</label>
                        </div>
                        <div class="col-md-3">
                            <select id="table" class="form-control select2" wire:model="selectedTable[]">
                                <option value="">-- Select Fields --</option>
                                <option value="projects.project_name">Project Name</option>
                                <option value="customers.first_name">Customer First Name</option>
                                <option value="customers.last_name">Customer Last Name</option>
                                <option value="departments.name">Department</option>
                                <option value="sub_departments.name">Sub-Department</option>
                            </select>
                        </div>

                        {{-- <div class="col-md-12 mt-5">
                            <label>Columns Selected.</label>
                            @php
                                $values = array_column($selectedColumns, 'value'); // Extract 'value' field
                            @endphp
                            <label>{{ implode(',', $values) }}</label>
                        </div> --}}
                    </div>
                </form>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <label>Add Filter</label>
                    </div>
                    <div class="col-md-3">
                        <select id="filtercolumns" class="form-control select2" style="line-height: 31px !important;">
                            <option value="">-- Select Filters --</option>
                            <option value="projects.project_name">Project Name</option>
                            <option value="customers.first_name">Customer First Name</option>
                            <option value="customers.last_name">Customer Last Name</option>
                            <option value="departments.name">Department</option>
                            <option value="sub_departments.name">Sub-Department</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select id="filteroperator" class="form-control">
                            <option value="=">=</option>
                            <option value=">">></option>
                            <option value="<">
                                < </option>
                            <option value=">=">>=</option>
                            <option value="<=">
                                <= </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input class="form-control" id="filtervalue" />
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <button type="button" id="filter-add" class="btn btn-primary"><i
                                class="icofont-save me-2 fs-6"></i>Save</button>
                    </div>
                </div>
                <form wire:submit="submitData">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 text-end">
                            <button type="submit" class="btn btn-primary"><i
                                    class="icofont-save me-2 fs-6"></i>Submit</button>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <label>Total Filters : {{ count($selectedFilters) }}</label>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            @if (count($selectedFilters) > 0)
                                <div class="col-md-12">
                                    <table class="table table-stripped table-bordered mt-5">
                                        <thead>
                                            <tr>
                                                <th>Column</th>
                                                <th>Operator</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($selectedFilters as $filter)
                                                <tr>
                                                    <td>{{ $filter['text'] }}</td>
                                                    <td>{{ $filter['operator'] }}</td>
                                                    <td>{{ $filter['value'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="card mt-2">
            <div class="card-body">
                <table class="table table-stripped table-bordered mt-5">
                    <thead>
                        @if (!empty($this->selectedColumns))
                            @foreach ($this->selectedColumns as $column)
                                <th>{{ $column['name'] }}</th>
                            @endforeach
                        @endif
                    </thead>
                    <tbody>

                        @foreach ($data as $row)
                            <tr>
                                @foreach ($this->selectedColumns as $key => $column)
                                    @php
                                        $column = preg_replace('/^[^.]+\./', '', $column);
                                        // Use a regex to get the part after 'AS'
                                        preg_match('/\bAS\s+(.*)/i', $column['value'], $match);

                                        // The part after 'AS' is in $match[1]
                                        $afterAs = trim($match[1]);
                                    @endphp
                                    <td>{{ $row->{$afterAs} }} </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</div>
@script
    <script>
        $(document).ready(function() {
            $(".select2").select2();

            Livewire.hook('morph.updating', ({
                component,
                cleanup
            }) => {
                $('.select2').select2();
            })
        })
        $("#table").change(function() {
            var data = $('#table').val();
            @this.set('selectedTable', data);
        })

        $("#btnSubmit").click(function() {
            event.preventDefault();

            //  Collect the data from the form
            let table = document.getElementById('table').value;
            let column = document.getElementById('column').value;

            // Call the Livewire method and pass the data
            Livewire.dispatch('submitData');
        })

        $("#filter-add").click(function() {
            let data = $('#filtercolumns').select2('data');
            if (data) {
                Livewire.dispatch('saveFilter', {
                    text : data[0].text,
                    column: data[0].id,
                    operator: $("#filteroperator").val(),
                    value: $("#filtervalue").val()
                })
                $("#filtercolumns").val('')
                $("#filteroperator").val('')
                $("#filtervalue").val('')
            }

        })
    </script>
@endscript
