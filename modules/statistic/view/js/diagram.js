/**
 * Формирование вывода цен в отформтированном виде
 *
 * @param number - цифры для преобразования
 * @param decimals - количество дробных чисел
 * @param dec_point - разделитель дробных
 * @param thousands_sep - разделитель тысячных
 * @return {string}
 */
function number_format(number, decimals, dec_point, thousands_sep) {
    number = (number + '')
        .replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + (Math.round(n * k) / k)
                .toFixed(prec);
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
        .split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '')
            .length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1)
            .join('0');
    }
    return s.join(dec).replace(dec_point+'00', '');
}


/**
 * Показ круговых диаграмм
 *
 * @param target - блок где будет показываться график
 * @param data - данные круговой диаграммы
 * @param unit - единица измерения
 */
function statisticShowPlot(target, data, unit)
{
    $.plot(target + ' .graph', data, {
        series: {
            pie: {
                show: true,
                label: {
                    show: false,
                    radius: 3/4,
                    formatter: function(label, series) {
                        if(label === null) return "NULL";
                        var title = label;
                        if(label.length > 20){ label = label.substring(0,20) + '..'; }
                        var unit = ' '+series.pie.unit;
                        var out = '<div title="'+title+' : '+number_format(series.data[0][1], 2, ',', ' ')+unit+'" style=";text-align:center;padding:2px;color:white;">';
                        out += label;
                        out += '<br/>';
                        out += Math.round(series.percent)+'%';
                        //out += series.data[0][1];
                        out += '</div>';
                        return out;
                    },
                    background: {
                        opacity: 0.5,
                        color: '#000'
                    }
                },
                unit: unit
            }
        },
        grid: {
            hoverable: true,
            clickable: true
        },
        legend: {
            container: target + ' .flc-plot',
            backgroundOpacity: 0.5,
            noColumns: 2,
            backgroundColor: "white",
            lineWidth: 0
        },
        tooltip: true,
        tooltipOpts: {
            content: "%p.0%, %s", // show percentages, rounding to 2 decimal places
            shifts: {
                x: 20,
                y: 0
            },
            defaultTheme: false,
            cssClass: 'chart-tooltip'
        },
        xaxis:{ labelWidth:5 }
    });
}

/**
 * Показ графика ключевых показателей в виде столбиков
 *
 * @param target - блок где будет показываться график
 * @param json_bars - json с панелями графика (список справа с заголовками линий)
 * @param json_values - json значения для посотроения графика
 * @param json_ticks - json подписи к столбикам
 */
function statisticShowKeyIndicator(target, json_bars, json_values, json_ticks)
{
    // Диаграмма в виде столбиков
    $(function () {
        $.plot(target, json_bars, {
            series: {
                bars: {
                    show: true,
                    barWidth: 0.15,
                    align: "center",
                    order: 1
                },
                absoluteValues: json_values
            },
            xaxis: {
                mode: "categories",
                ticks: json_ticks,
                tickLength: 1
            },
            grid: {
                hoverable: true,
                borderWidth:0
            },
            yaxis: {
                allowDecimals: false
            }
        });
    });

    var previousPoint;
    var showTooltip = function(x, y, contents) {
        $("#sellChartTooltip").remove();
        $('<div id="sellChartTooltip" class="chart-tooltip"/>').html(contents).css( {
            top: y + 10,
            left: x + 10
        }).appendTo("body").fadeIn(200);
    };

    /**
     * Наведение на подсказки на графике
     */
    $(".graph").bind("plothover", function(e, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;                        
                var
                    pointData = item.series.absoluteValues[item.seriesIndex][item.dataIndex];
                    
                var tooltipText = pointData;
                showTooltip(item.pageX, item.pageY, tooltipText);
            }
        }
        else {
            $("#sellChartTooltip").remove();
            previousPoint = null;            
        }
    });
}

/**
 * Показ статистики в виде волн
 *
 * @param target - блок где будет показываться график
 * @param wrapper - блок с отображаением
 * @param json_values - json значения для построения графика
 */
function statisticShowWaves(target, wrapper, json_values)
{
    var currentWave = $("#presetWaves a.act", $(wrapper)).data('value');
    //Определим нужные данные
    var current_data = json_values[currentWave];

    $(function() {

        //Данные по X
        var x_data = [];
        $.each(current_data['data']['ticks'], function(i, v){
            x_data.push([i, v]);
        });
        //Данные по Y
        var y_data = [];
        $.each(current_data['data']['values'], function(i, v){
            y_data.push([i, v]);
        });

        var plot = $.plot(target, [
            {
                data: y_data,
                label: current_data['label'],
                color: 'rgb(203,75,75)'
            }
        ], {
            series: {
                lines: {
                    show: true
                },
                points: {
                    show: true
                }
            },
            xaxis: {
                ticks: x_data,
                tickLength: 1
            },
            grid: {
                hoverable: true,
                clickable: true,
                borderWidth: 0
            }
        });

        var previousPoint;
        var showTooltip = function(x, y, contents) {
            $("#sellChartTooltip").remove();
            $('<div id="sellChartTooltip" class="chart-tooltip"/>').html(contents).css( {
                top: y + 10,
                left: x + 10
            }).appendTo("body").fadeIn(200);
        };

        /**
         * Наведение на подсказки на графике
         */
        $(".graph", $(wrapper)).on("plothover", function(e, pos, item) {
            if (item) {
                if (previousPoint != item.dataIndex) {
                    previousPoint = item.dataIndex;
                    var tooltipText = current_data['data']['values'][item.datapoint[0]] + " " + current_data['y_unit'] + "<br/>" + current_data['data']['ticks'][item.datapoint[0]];
                    showTooltip(item.pageX, item.pageY, tooltipText);
                }
            }
            else {
                $("#sellChartTooltip").remove();
                previousPoint = null;
            }
        });
    });
}