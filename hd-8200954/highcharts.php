

$(function(){

    var categories = " ";

    Highcharts.chart('teste', {
        chart: {
            type: 'bar'
        },
        title: {
            text: 'Historic World Population by Region'
        },
        subtitle: {
            text: 'Source: <a href="https://en.wikipedia.org/wiki/World_population">Wikipedia.org</a>'
        },
        xAxis: {
            labels:{
                style:{
                    color:'red',
                    fontSize:"13px",
                }
            },
            categories: [categories],
            title: {
                text: null
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Population (millions)',
                align: 'high'
            },
            labels: {
                overflow: 'justify',
                style:{
                    color:'green',
                    fontSize:"11px",
                }
            }
        },
        tooltip: {
            valueSuffix: ' millions'
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true,
                }
            },
            series: {
                dataLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        color: 'blue',
                        fontSize:"10px"
                    }
                }
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -40,
            y: 80,
            floating: true,
            borderWidth: 1,
            backgroundColor:
                Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
            shadow: true
        },
        credits: {
            enabled: false
        },
        series: [{
            name: 'Agendado',
            data: [107, 31, 635, 203, 2]
        }, {
            name: 'Realizado',
            data: [814, 841, 3714, 727, 31]
        }, {
            name: 'Cancelado',
            data: [1216, 1001, 4436, 738, 40]
        }]
    });


});