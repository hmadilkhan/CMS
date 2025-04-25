<div wire:ignore>
    <div class="card shadow-sm h-100">
        <input type="hidden" id="chart-data" value='@json($chartData)' />
        <div class="card-header bg-white border-0">
            <h5 class="card-title mb-0">PTO Approvals This Month</h5>
        </div>
        <div class="card-body">
            <div id="ptoApprovalChart" style="min-height: 336px;"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const initialChartData = @json($chartData);
        let chart3 = null;
        let lastData = null;

        function initChart(data) {
            if (!data) return;

            const rawLabels = data.labels || ['No Data'];
            const rawData = data.data || [0];
            const colors3 = data.colors || ['#ccc'];

            const newData = {
                labels: rawLabels,
                data: rawData,
                colors: colors3
            };

            lastData = newData;

            // Validate and prepare data
            const labels3 = Array.isArray(rawLabels) ? rawLabels.filter(label => label !== null && label !== '') : [
                'No Data'
            ];
            const data3 = Array.isArray(rawData) ? rawData.map(value => {
                const num = parseFloat(value);
                return isNaN(num) || num < 0 ? 0 : num;
            }) : [0];

            // Ensure we have at least one valid data point
            if (data3.length === 0 || data3.every(val => val === 0)) {
                labels3.length = 1;
                labels3[0] = 'No Data';
                data3.length = 1;
                data3[0] = 0;
            }

            var options3 = {
                series: data3,
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
                            // console.log('Chart mounted with data:', labels3);
                        },
                        updated: function(chartContext, config) {
                            // console.log('Chart updated with new data:', labels3);
                        }
                    }
                },
                labels: labels3,
                colors: colors3,
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
                            return val + ' PTO approvals'
                        }
                    }
                }
            };

            // Destroy existing chart if it exists
            if (chart3) {
                chart3.destroy();
            }

            // Create new chart
            const chartElement = document.querySelector("#ptoApprovalChart");
            if (chartElement) {
                chart3 = new ApexCharts(chartElement, options3);
                chart3.render();
            } else {
                console.error('Chart element not found');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialData = @json($chartData);
            initChart(initialData);
        });

        // Listen for Livewire event (custom)
        window.addEventListener('refreshChart', function() {
            const chartData = JSON.parse(document.getElementById('chart-data').value);
            initChart(chartData);
        });
        
    </script>
@endpush
