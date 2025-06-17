<div wire:ignore>
    <div class="card shadow-sm h-100">
        <div class="card-header bg-white border-0">
            <h5 class="card-title mb-0">Department Time Distribution</h5>
        </div>
        <div class="card-body">
            <input type="hidden" id="department-chart-data" value='@json($departmentChartData)' />
            <div id="departmentTimeChart" style="min-height: 336px;"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const initialDepartmentData = @json($departmentChartData);
        let departmentChart = null;
        let lastDepartmentData = null;

        function initDepartmentChart(data) {
            if (!data) return;

            const rawLabels = data.labels || ['No Data'];
            const rawData = data.data || [0];
            const colors = data.colors || ['#fff'];

            const newData = {
                labels: rawLabels,
                data: rawData,
                colors: colors
            };

            lastDepartmentData = newData;

            // Validate and prepare data
            const labels = Array.isArray(rawLabels) ? rawLabels.filter(label => label !== null && label !== '') : [
                'No Data'];
            const dataPoints = Array.isArray(rawData) ? rawData.map(value => {
                const num = parseFloat(value);
                return isNaN(num) || num < 0 ? 0 : num;
            }) : [0];

            // Ensure we have at least one valid data point
            if (dataPoints.length === 0 || dataPoints.every(val => val === 0)) {
                labels.length = 1;
                labels[0] = 'No Data';
                dataPoints.length = 1;
                dataPoints[0] = 0;
            }

            var options = {
                series: dataPoints,
                chart: {
                    height: 400,
                    type: 'pie',
                    toolbar: {
                        show: false,
                    },
                    animations: {
                        enabled: true
                    },
                    events: {
                        mounted: function(chartContext, config) {
                            // console.log('Department chart mounted with data:', labels);
                        },
                        updated: function(chartContext, config) {
                            // console.log('Department chart updated with new data:', labels);
                        }
                    }
                },
                labels: labels,
                colors: colors,
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '14px',
                    markers: {
                        width: 12,
                        height: 12,
                        radius: 12
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 6
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%'
                        },
                        expandOnClick: true
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        colors: ['#fff']
                    },
                    formatter: function(val) {
                        return val.toFixed(1) + '%'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(1) + ' days'
                        }
                    }
                }
            };

            // Destroy existing chart if it exists
            if (departmentChart) {
                departmentChart.destroy();
            }

            // Create new chart
            const chartElement = document.querySelector("#departmentTimeChart");
            if (chartElement) {
                departmentChart = new ApexCharts(chartElement, options);
                departmentChart.render();
            }
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            initDepartmentChart(initialDepartmentData);
        });

        // Listen for Livewire event (custom)
        window.addEventListener('refreshDepartmentChart', function() {
            const chartData = JSON.parse(document.getElementById('department-chart-data').value);
            initDepartmentChart(chartData);
        });
    </script>
@endpush
