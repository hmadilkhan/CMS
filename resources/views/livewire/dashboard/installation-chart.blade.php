<div class="card shadow-sm h-100">
    <div class="card-header bg-white border-0">
        <h5 class="card-title mb-0">Installations This Month</h5>
    </div>
    <div class="card-body">
        <div id="installationChart" style="min-height: 336px;"></div>
    </div>
</div>

@section('scripts')
<script>
    // Validate and prepare data
    const labels2 = @json($chartData['labels']).filter(label => label !== null && label !== '');
    const data2 = @json($chartData['data']).map(value => {
        const num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    });
    const colors2 = @json($chartData['colors']);

    console.log('Installation Chart Data:', {
        labels: labels2,
        data: data2,
        colors: colors2
    });

    var options2 = {
        series: data2,
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
        labels: labels2,
        colors: colors2,
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
                    return val + ' installations'
                }
            }
        }
    };

    var chart2 = new ApexCharts(document.querySelector("#installationChart"), options2);
    chart2.render();
</script>
@endsection 