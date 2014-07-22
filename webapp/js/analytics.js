$(function() {
    $(document).on("click", "#Btn1", function() {
        getEventList();
    });

    $(document).on("click", "#Btn2", function() {
        getLocationList();
    });

    function sendAjax(url, callBack) {
        $.ajax({
            url: url,
            beforeSend: function() {
                $("#Loader").css({ 'display': 'block' });
            },
            complete: function() {
                $("#Loader").hide();
            },
            error: function(error) {
                alert(error.msg || error.message || "Unexpected error.");
            },
            success: function(data) {
                var response = JSON.parse(data);

                if (response.status) {
                    callBack(response);
                } else {
                    callBack({});
                }
            }
        });
    }

    function getEventList() {
        sendAjax("/demo/analytics/AjaxGetEventList", function(response) {
            $('#a-container').highcharts({
                chart: {
                    type: 'column',
                    options3d: {
                        enabled: true,
                        alpha: 15,
                        beta: 20,
                        viewDistance: 25,
                        depth: 50
                    },
                    marginTop: 80,
                    marginRight: 40
                },
                title: {
                    text: 'Digital Asset Simulation List'
                },
                subtitle: {
                    text: 'Represents count of triggered events for each Digital Asset'
                },
                xAxis: {
                    categories: response.categories || []
                },
                yAxis: {
                    allowDecimals: false,
                    min: 0,
                    title: {
                        text: 'Count of events'
                    }
                },
                tooltip: {
                    headerFormat: '<b>{point.key}</b><br>',
                    pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: {point.y} / {point.stackTotal}'
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        depth: 50
                    }
                },
                series: response.series || []
            });
        });
    }

    function getLocationList() {
        sendAjax("/demo/analytics/AjaxGetLocationList", function(response) {
            $('#location-container').highcharts({
                chart: {
                    type: 'column',
                    options3d: {
                        enabled: true,
                        alpha: 15,
                        beta: 20,
                        viewDistance: 25,
                        depth: 50
                    },
                    marginTop: 80,
                    marginRight: 40
                },
                title: {
                    text: 'Digital Asset Location'
                },
                xAxis: {
                    categories: response.categories || []
                },
                yAxis: {
                    allowDecimals: false,
                    min: 0,
                    title: {
                        text: 'Count of digital assets'
                    }
                },
                tooltip: {
                    headerFormat: '<b>{point.key}</b><br>',
                    pointFormat: '<span style="color:{series.color}">\u25CF</span> {series.name}: {point.y} / {point.stackTotal}'
                },
                plotOptions: {
                    column: {
                        stacking: 'normal',
                        depth: 50
                    }
                },
                series: response.series || []
            });
        });
    }

    function load() {
        getEventList();
        getLocationList();
    }

    load();
});
