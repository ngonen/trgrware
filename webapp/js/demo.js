(function() {
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
                token: $("#Token").val()
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

    getSecurityToken();
})();
