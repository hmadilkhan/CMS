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
                            <select id="selectedFields" class="form-control select2">
                                <option value="">-- Select Fields --</option>
                                <optgroup class="default" label="Customer">
                                    <option value="customers.first_name">Customer First Name</option>
                                    <option value="customers.last_name">Customer Last Name</option>
                                    <option value="customers.email">Email</option>
                                    <option value="customers.city">City</option>
                                    <option value="customers.state">State</option>
                                    <option value="customers.zipcode">Zip Code</option>
                                </optgroup>
                                <optgroup class="default" label="Project">
                                    <option value="projects.project_name">Project Name</option>
                                    <option value="departments.name">Department</option>
                                    <option value="sub_departments.name">Sub-Department</option>
                                    <option value="sales_partners.name">Sales Partner</option>
                                    <option value="customers.sold_date">Sold Date</option>
                                    <option value="customers.panel_qty">ُPanel Qty</option>
                                    <option value="customers.panel_qty">Inverter Qty</option>
                                    <option value="module_types.name">Module Type</option>
                                    <option value="inverter_types.name">Inverter Type</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="d-grid gap-2 d-md-block mt-2 mb-2">
                            @foreach ($this->selectedColumns as $colKey => $column)
                                <button type="button" wire:click="deleteColumn({{ $colKey }})"
                                    style="cursor: pointer" class="badge bg-primary">{{ $column['text'] }} <i
                                        class="icofont-ui-delete text-white"></i></button>
                            @endforeach
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
                            <optgroup class="default" label="Customer">
                                <option value="customers.first_name">Customer First Name</option>
                                <option value="customers.last_name">Customer Last Name</option>
                                <option value="customers.email">Email</option>
                                <option value="customers.city">City</option>
                                <option value="customers.state">State</option>
                                <option value="customers.zipcode">Zip Code</option>
                            </optgroup>
                            <optgroup class="default" label="Project">
                                <option value="projects.project_name">Project Name</option>
                                <option value="departments.name">Department</option>
                                <option value="sub_departments.name">Sub-Department</option>
                                <option value="sales_partners.name">Sales Partner</option>
                                <option value="customers.sold_date">Sold Date</option>
                                <option value="customers.panel_qty">ُPanel Qty</option>
                                <option value="customers.panel_qty">Inverter Qty</option>
                                <option value="module_types.name">Module Type</option>
                                <option value="inverter_types.name">Inverter Type</option>
                            </optgroup>
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
                                class="icofont-save me-2 fs-6"></i>Add Filter</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <label>Total Filters : {{ count($selectedFilters) }}</label>
                    </div>
                    @if (count($selectedFilters) > 0)
                        <div class="d-grid gap-2 d-md-block mt-2 mb-2">
                            @foreach ($selectedFilters as $colKey => $filter)
                                <button type="button" wire:click="deleteFilter({{ $colKey }})"
                                    style="cursor: pointer"
                                    class="badge bg-primary">{{ $filter['text'] . ' ' . $filter['operator'] . ' ' . $filter['value'] }}
                                    <i class="icofont-ui-delete text-white"></i></button>
                            @endforeach
                        </div>
                    @endif
                    {{-- <div class="col-md-6">
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
                    </div> --}}
                </div>
                <form wire:submit="submitData">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 text-end">
                            <button type="submit" class="btn btn-primary"><i class="icofont-save me-2 fs-6"></i>Run
                                Report</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section>
        @if (count($selectedColumns) > 0)
            <div class="card mt-2">
                <div class="card-body">
                    <table class="table table-stripped table-bordered mt-5">
                        <thead>
                            @if (!empty($this->selectedColumns))
                                @foreach ($this->selectedColumns as $column)
                                    <th>{{ $column['text'] }}</th>
                                @endforeach
                            @endif
                        </thead>
                        <tbody>
                            @if (!empty($data))
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
                            @elseif(!empty($data) && count($selectedColumns) > 0)
                                <tr>
                                    <td colspan="{{ count($selectedColumns) }}">No Record Found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
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
        $("#selectedFields").change(function() {
            let data = $('#selectedFields').val();
            let text = $("#selectedFields option:selected").text();
            console.log(data, text);

            // @this.set('selectedTable', data);
            Livewire.dispatch('selectedFields', {
                value: data,
                text: text
            });
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
                    text: data[0].text,
                    column: data[0].id,
                    operator: $("#filteroperator").val(),
                    value: $("#filtervalue").val()
                })
                $("#filtercolumns").val('')
                $("#filteroperator").val('')
                $("#filtervalue").val('')
            }

            function deleteColumn(index) {
                alert();
            }
        })
    </script>
@endscript
