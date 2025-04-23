<div class="card shadow-sm h-100">
    <div class="card-header bg-white border-0">
        <h5 class="card-title mb-0">Average Time Per Department</h5>
    </div>
    <div class="card-body">
        <div id="departmentTimeChart" style="min-height: 336px;"></div>
    </div>
</div>

@section('scripts')
<script>
    // Validate and prepare data
    const labels = @json($chartData['labels']).filter(label => label !== null && label !== '');
    const data = @json($chartData['data']).map(value => {
        const num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    });
    const colors = @json($chartData['colors']);

    var options = {
        series: data,
        chart: {
            height: 400,
            type: 'pie',
            toolbar: {
                show: false,
            },
            animations: {
                enabled: true
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
                    return val.toFixed(1) + ' hours'
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#departmentTimeChart"), options);
    chart.render();
</script>
@endsection
