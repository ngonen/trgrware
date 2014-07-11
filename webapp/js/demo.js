(function() {
    var assets = [23423424, 34534, 345, 53452534, 354, 325,123, 22, 45435, 534535],
        assetIds = {
            'w': [],
            'i': [],
            'f': []
        };

//    getSecurityToken();

    function showButtons() {
        $("#GetIncidentInfo").attr("disabled", false);
        $("#GetWeatherInfo").attr("disabled", false);
        $("#GetSegmentSpeedInfo").attr("disabled", false);
    }

    function getSecurityToken() {
        $.ajax({
            url: "/demo/default/AjaxGetSecurityToken",
            type: "POST",
            beforeSend: function() {
                $("#Results").hide();
            },
            error: function(error) {
                alert(error.msg || error.message || "Unexpected error.");
            },
            success: function(data) {
                var response = JSON.parse(data);

                $("#Token").val(response.token);

                showButtons();
            }
        });
    }

    function getInfo(url, data, callBack) {
        $.ajax({
            url: url,
            type: "POST",
            data: data,
            beforeSend: function() {
                $("#Loader").css({ 'display': 'block' });
                $("#Results").hide();
            },
            complete: function() {
                $("#Loader").hide();
            },
            error: function(error) {
                alert(error.msg || error.message || "Unexpected error.");
            },
            success: callBack
        });
    }

    function registerEvent(data, url) {
        $.ajax({
            url: url ? url : '/demo/default/RegisterEvent',
            type: "POST",
            data: data,
            beforeSend: function() {
                $("#Loader").css({ 'display': 'block' });
                $("#EventRegistration > p").hide();
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
                    $("#EventRegistration > p").html(response.message).show();
                } else {
                    alert(response.errorMessage);
                }
            }
        });
    }

    function deleteAssetInfo(url, data) {
        $.ajax({
            url: url,
            data: data,
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

                alert(response.status ? response.message : response.errorMessage);
            }
        });
    }

    function runCronJob(url) {
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
//                var response = JSON.parse(data);
            }
        });
    }

    $(document).on("click", "#GetIncidentInfo", function() {
        var data = {
            center: "34.014|-118.2869",
            radius: "7",
            token: $("#Token").val()
        };

        getInfo("/demo/default/AjaxGetIncidentInfo", data, function(data) {
            var response = JSON.parse(data),
                incidents = xmlToJSON.parseString(response.incidents).Inrix[0].Incidents[0].Incident;

            if (incidents.length > 0) {
                $("#Results").html("We found " + incidents.length + " incidents.").show();
            } else {
                $("#Results").html("Incidents were not found.").show();
            }
        });
    });

    $(document).on("click", "#GetWeatherInfo", function() {
        var data = {
                center: "34.014|-118.2869",
                radius: "5",
                token: $("#Token").val(),
                refreshKey: "344dgfs"
            };

        getInfo("/demo/default/AjaxGetWeatherInRadius", data, function(data) {
            var response = JSON.parse(data),
                weather = xmlToJSON.parseString(response.weather).Inrix[0].Weather[0].Conditions[0].Station;

            if (weather.length > 0) {
                $("#Results").html("We found " + weather.length + " weather conditions.").show();
            } else {
                $("#Results").html("Weather conditions were not found.").show();
            }
        });
    });

    $(document).on("click", "#GetSegmentSpeedInfo", function() {
        var data = {
                center: "34.014|-118.2869",
                radius: "1",
                token: $("#Token").val()
            };

        getInfo("/demo/default/AjaxGetSegmentSpeedInRadius", data, function(data) {
            var response = JSON.parse(data),
                segmentSpeedResults = xmlToJSON.parseString(response.segmentSpeed).Inrix[0].SegmentSpeedResultSet[0].SegmentSpeedResults[0].Segment;

            if (segmentSpeedResults.length > 0) {
                $("#Results").html("We found " + segmentSpeedResults.length + " speed conditions.").show();
            } else {
                $("#Results").html("Speed conditions were not found.").show();
            }
        });
    });

    $(document).on("click", "#GetToken", getSecurityToken);

    $(document).on("click", "#PopUp, button[name='cancel']", function() {
        $(".modal-dialog-bg").toggle();
        $(".modal-dialog.p6n-popup").toggle();
    });

    $(document).on("click", "#SimulateCallback", function() {
        $.ajax({
            url: "/demo/default/GalaxyCallBack",
            beforeSend: function() {
                $("#Loader").css({ 'display': 'block' });
                $("#CallBackInfo").hide().html();
            },
            complete: function() {
                $("#Loader").hide();
            },
            error: function(error) {
                alert(error.msg || error.message || "Unexpected error.");
            },
            success: function(data) {
                var response = JSON.parse(data);

                $("#CallBackInfo").html(response.result).show();
            }
        });
    });

    $(document).on("click", "#BtnWeather", function() {
        var id = Math.random(0, 10000),
            data = {
                asset_id: "asset-" + id,
                eventType: "weather_temperature",
                center: "34.014|-118.2869",
                radius: "4",
                condition: "Over",
                threshold: 30,
                callbackURL: "http://gae-gh.appspot.com/"
            };

        assetIds['w'].push(id);
        registerEvent(data);
    });

    $(document).on("click", "#BtnWeatherSpeed", function() {
        var id = Math.random(0, 10000),
            data = {
                asset_id: "asset-" + id,
                eventType: "weather_wind",
                center: "34.014|-118.2869",
                radius: "2",
                speed: 30,
                callbackURL: "http://gae-gh.appspot.com/"
            };

        assetIds['w'].push(id);
        registerEvent(data);
    });

    $(document).on("click", "#BtnWrongEventType", function() {
        var id = Math.random(0, 10000),
            data = {
                asset_id: "asset-" + id,
                eventType: "safdaf",
                center: "34.014|-118.2869",
                radius: "2",
                speed: 30,
                callbackURL: "http://gae-gh.appspot.com/"
            };

        assetIds['w'].push(id);
        registerEvent(data);
    });

    $(document).on("click", "#BtnRegTwitter", function() {
        var id = Math.random(0, 10000),
            data = {
                asset_id: "asset-" + id,
                eventType: "twitter_hash_tag",
                retriggerPeriod: 4,
                hashtag: "someTag",
                campaigns: JSON.stringify([
                    { count: 7, callbackURL: "http://some.url.com" },
                    { count: 4, callbackURL: "http://some.url.com" }
                ])
            };

        registerEvent(data);
    });

    $(document).on("click", "#BtnTrafficInfo", function() {
        var id = Math.random(0, 10000),
            data = {
                id: "asset-" + id,
                event: "incidents",
                center: "34.014|-118.2869",
                radius: "1",
                url: "http://google.com/"
            };

        assetIds['i'].push(id);
        registerEvent(data);
    });

    $(document).on("click", "#BtnTrafficFlow", function() {
        var id = Math.random(0, 10000),
            data = {
                id: "asset-" + id,
                event: "speed",
                center: "34.014|-118.2869",
                radius: "4",
                url: "http://football.ua/",
                condition: "Over",
                threshold: Math.random(0, 10) * 10
            };

        assetIds['f'].push(id);
        registerEvent(data);
    });

    $(document).on("click", "#BtnUpdateTrafficFlow", function() {
        var data = {
                id: "asset-" + assetIds['f'][1],
                event: "speed",
                center: "34.014|-118.286",
                radius: 0.5,
                url: "http://football.ua/ukraine/fsdf",
                mpx: 20
            },
            url = "/demo/default/UpdateEvent";

        registerEvent(data, url);
    });

    $(document).on("click", "#BtnUpdateAsset", function() {
        var data = {
                id: "asset-" + assetIds['f'][1],
                center: "35|-100",
                events: JSON.stringify({
                    'weather': "kuku.com",
                    'speed': 'blabla.com'
                })
            },
            url = "/demo/default/UpdateAsset";

        registerEvent(data, url);
    });

    $(document).on("click", "#BtnCronTest", function() {
        $.ajax({
            url: '/demo/Cron/WeatherTemperatureNotifier',
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
//                var response = JSON.parse(data);
            }
        });
    });

    $(document).on("click", "#BtnCronTestAccident", function() {
        $.ajax({
            url: '/demo/Cron/TrafficIncidentsNotifier',
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
//                var response = JSON.parse(data);
            }
        });
    });

    $(document).on("click", "#BtnCronTestFlow", function() {
        $.ajax({
            url: '/demo/Cron/TrafficSpeedNotifier',
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
//                var response = JSON.parse(data);
            }
        });
    });

    $(document).on("click", "#BtnCronTestWind", function() {
        $.ajax({
            url: '/demo/Cron/WeatherWindSpeedNotifier',
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
//                var response = JSON.parse(data);
            }
        });
    });

    $(document).on("click", "#BtnDeleteEvent", function() {
        var url = '/demo/Default/DeleteEvent',
            data = {
                assetId: $("#assetId").val(),
                eventType: $("#eventType").val()
            };

        deleteAssetInfo(url, data);
    });

    $(document).on("click", "#BtnDeleteAsset", function() {
        var url = '/demo/Default/DeleteAsset',
            data = {
                assetId: $("#assetId").val()
            };

        deleteAssetInfo(url, data);
    });

    $(document).on("click", "#BtnCronTestSun", function() {
        runCronJob('/demo/Cron/WeatherSunNotifier');
    });

    $(document).on("click", "#BtnCronTestRain", function() {
        runCronJob('/demo/Cron/WeatherRainNotifier');
    });

    $(document).on("click", "#BtnCronTestSnow", function() {
        runCronJob('/demo/Cron/WeatherSnowNotifier');
    });

    $(document).on("click", "#BtnCronTestThunderstorms", function() {
        runCronJob('/demo/Cron/WeatherThunderstormsNotifier');
    });
})();

