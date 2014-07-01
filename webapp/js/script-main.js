// Setup Google Maps layer
var mapOptions = {
    center: new google.maps.LatLng(40.7560341, -73.9869242),
    zoom: 12,
    mapTypeId: google.maps.MapTypeId.ROADMAP
};
var map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
var markers = [];
var incidentMarkers = [];
var weatherStations = [];
var assets = [];
var props;
var active_asset;
var trigger_type;
var refreshKey;

// Add INRIX Tile layer (see inrix.layer.js for details)
var traffic = new InrixTileLayer(map);

// Subscribe to INRIX AuthToken readiness and fetch the Bing Maps layer
$.subscribe('Platform.AuthTokenChanged', function() {
    if ($("#accidents")[0].checked) {
        traffic.show();
    }
});

var IncidentMgr = new Inrix.IncidentManager();

var getUnigueID = (function () {
    function getStamp() {
        return "_" + ((new Date()).valueOf()).toString(32);
    }
    function padLeft(str, len, charCode) {
        return ((len = len - str.length) > 0 ? Array(len + 1).join(charCode || " ") : "") + str;
    }
    var pk = getStamp(),
        pkn = 0;
    return function () {
        if (pkn > 999) {
            pkn = 0;
            pk = getStamp();
        }
        return pk + padLeft((++pkn).toString(32), 3, "0");
    };
}());

function showTriggerPopup(event) {
    var data = event.target.dataset,
        trigger;
    trigger_type = data.type;
    trigger = active_asset.triggers.filter(function(trigger) { return trigger.type === trigger_type; })[0];
    if (trigger) {
        $(data.popup + " .prop").toArray().forEach(function(el) {
            if (el.type === "checkbox") {
                el.checked = trigger[el.dataset.property];
            } else {
                el.value = trigger[el.dataset.property];
            }
        });
    }
    showPopup(data.popup);
}

function showPropertiesPopup() {
    showPopup("#asset_popup");
    $("#asset_popup .prop").toArray().forEach(function(el) {
       el.value =  active_asset[el.dataset.property];
    });
}

function clearPopupFields() {
    $('.popup .prop').toArray().forEach(function(el) {
        switch (el.type) {
            case "text":
                el.value = "";
                break;
            case "checkbox":
                el.checked = false;
                break;
            case "select-one":
                el.options.selectedIndex = 0;
                break;
        }
    });
}

function sendAssetUpdateRequest(asset) {
    var data = {
        asset_id: asset.id,
        center: asset.lat + "|" + asset.lng
    };
    $.ajax({
        url: "/demo/default/UpdateAsset",
        type: "POST",
        data: data,
        success: function() { console.log("Triggers updated"); },
        error: function() { console.log("Error on asset updating"); }
    });
}

function updateAsset(event) {
    var marker,
        id = active_asset? active_asset.id : props.id,
        type = active_asset? active_asset.type : props.type,
        properties = getProperties(event.target.dataset.popup),
        lat, lng;
        
    properties.id = id;
    properties.type = type;
    marker = markers.filter(function(m) { return m.title === properties.id; })[0];
    marker.setPosition(new google.maps.LatLng(properties.lat, properties.lng));
    
    if (active_asset) {
        lat = active_asset.lat;
        lng = active_asset.lng;
        
        active_asset.setProperties(properties);
        
        if ((active_asset.lat !== lat || active_asset.lng !== lng) && active_asset.triggers.length) {
            sendAssetUpdateRequest(active_asset);
        }
    } else {
        assets.push(new Asset(properties));
    }
    
    hidePopup();
}

function sendTriggerUpdateRequest(trigger, isNewTrigger) {
    var url = isNewTrigger ? "/demo/default/RegisterEvent" : "/demo/default/UpdateEvent";
    $.ajax({
        url: url,
        type: "POST",
        data: trigger.getData(),
        success: function() { console.log("Trigger was successfully registered.") },
        error: function() { console.log("Trigger was not registered.") }
    });
}

function updateTrigger(event) {
    var properties = getProperties(event.target.dataset.popup),
        trigger;
    properties.type = trigger_type;
    properties.asset_id = active_asset.id;
    trigger = active_asset.triggers.filter(function(trigger) { return trigger.type === trigger_type; })[0];
    if (trigger) {
        //change trigger properties
        trigger.setProperties(properties);
        sendTriggerUpdateRequest(trigger, false);
    } else {
        //create trigger
        switch (trigger_type) {
            case "traffic_accident":
                trigger = new AccidentTrigger(properties);
                break;
            case "traffic_flow":
                trigger = new FlowTrigger(properties);
                break;
            case "weather_event":
                trigger = new WeatherEventTrigger(properties);
                break;
            case "weather_temperature":
                trigger = new TemperatureTrigger(properties);
                break;
            case "twitter":
                trigger = new TwitterTrigger(properties);
                break;
        }
        active_asset.triggers.push(trigger);
        sendTriggerUpdateRequest(trigger, true);
    }   
    hidePopup();
}

function getProperties(popup) {
    var properties = {};
    $(popup + " .prop").toArray().forEach(function(el) {
        if (el.type === "checkbox") {
            properties[el.dataset.property] = el.checked;
        } else {
            properties[el.dataset.property] = el.value;
        }
    });
    return properties;
}

function onCancel() {
    if (!active_asset) {
        marker = markers.filter(function(m) { return m.title === props.id; })[0];
        marker.setMap(null);
        markers.splice(markers.indexOf(marker), 1);
    }
    hidePopup();
}

function displayMenu(ev){
    var menu = $("#context_menu")[0];
    if (ev.clientY + menu.clientHeight > innerHeight) {
        menu.style.top =  pageYOffset - menu.clientHeight + ev.clientY + "px";
    } else {
        menu.style.top = pageYOffset + ev.clientY + "px";
    }
    if (ev.clientX + menu.clientWidth > innerWidth) {
        menu.style.left =  pageXOffset - menu.clientWidth + ev.clientX + "px";
    } else {
        menu.style.left = pageXOffset + ev.clientX + "px";  
    }
    if (ev.clientX + menu.clientWidth + 300 > innerWidth) {
        $(".submenu, .traffic_sub_menu, .weather_sub_menu, .social_sub_menu").css("left", -150 + "px");
    } else {
        $(".submenu, .traffic_sub_menu, .weather_sub_menu, .social_sub_menu").css("left", 150 + "px");
    }
    /*if (ev.clientY + menu.clientHeight + 105 > innerHeight) {
        $(".submenu").css("top", -70 + "px");
    } else {
        $(".submenu").css("top", "0");
    }*/
    menu.style.visibility = "visible";
    ev.preventDefault();
}

function hideMenu() {
    $("#context_menu").css("visibility", "hidden");
}

function showIncidents(incidents) {
    if (map.getZoom() > 11) {
        clearMap(incidentMarkers);
        incidents.forEach(function(incident) {
            incidentMarkers.push(new google.maps.Marker({
                position: new google.maps.LatLng(incident.latitude, incident.longitude),
                map: map,
                icon: 'img/incidentPin@2x-small.png',
                title: 'incident'
            }));
        });
    }
}

function clearMap(markersArray) {
    markersArray.forEach(function(marker) {
        marker.setMap(null);
    });
    markersArray.length = 0;
}

function showWeather(data) {
    var response = JSON.parse(data),
        stations;
    if (response.refreshKey === refreshKey) {
        clearMap(weatherStations);
        try {
            stations = xmlToJSON.parseString(response.weather).Inrix[0].Weather[0].Conditions[0].Station;
            stations.forEach(function(station) {
               var temperature = station.Current[0].Temperature[0]._attr.actual._value;
               var degreeClass = getStandardDegreeName(temperature);
               var location = station._attr.point._value;
               var lat = parseFloat(location.substring(0, location.indexOf("|")));
               var lng = parseFloat(location.substring(location.indexOf("|") + 1, location.length));
               weatherStations.push(new MarkerWithLabel({
                   position: new google.maps.LatLng(lat, lng),
                   draggable: false,
                   raiseOnDrag: false,
                   map: map,
                   icon: 'img/x.gif',
                   labelContent: temperature + "\u00B0",
                   labelAnchor: new google.maps.Point(16, 19),
                   labelClass: "weather_station " + degreeClass,
                   title: "weather station"
                }));
            });
        } catch(err) {
            console.log("Cannot parse responce");
        }
    }
}

function getIncidents() {
    var bounds = map.getBounds(),
        ne = bounds.getNorthEast(),
        sw = bounds.getSouthWest();
    var params = {
        outputfields: 'all',
        corner1: ne.lat() +'|'+ ne.lng(),
        corner2: sw.lat() +'|'+ sw.lng(),
        incidentType: "Incidents",
        incidentSource: "All",
        success:function (incidents) {
            showIncidents(incidents);
        }
    };
    IncidentMgr.getIncidentsInBox(params);
}

function rad(x) {
    return x * Math.PI / 180;
}

function getDistance(p1, p2) {
    var R = 6378137; // Earth’s mean radius in meter
    var dLat = rad(p2.lat() - p1.lat());
    var dLong = rad(p2.lng() - p1.lng());
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) *
            Math.sin(dLong / 2) * Math.sin(dLong / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    var d = R * c;
    return d / 1000;
}

function getWeather() {
    var center = map.getCenter(),
        bounds = map.getBounds(), 
        cor1 = bounds.getNorthEast(), 
        cor2 = bounds.getSouthWest(), 
        cor3 = new google.maps.LatLng(cor1.lat(), cor2.lng()),
        multiplier = 0.621371, //to convert kilometers to miles
        width = getDistance(cor1, cor3) * multiplier;
    refreshKey = getUnigueID();
    var data = {
            center: center.lat() + "|" + center.lng(),
            radius: width / 2,
            token: "vHLYSm6wX-CKUT89bPCLg*fhk*asdVvRSa813n5GMhs|",
            refreshKey: refreshKey
        };
    $.ajax({
        url: "/demo/default/AjaxGetWeatherInRadius",
        type: "POST",
        data: data,
        success: showWeather
    });
}

function getMapInfo() {
    if (map.getZoom() > 11) {
        if ($("#temperature")[0].checked) {
            getWeather();
        }
        if ($("#accidents")[0].checked) {
            getIncidents();
        }
    } else {
        refreshKey = null;
        clearMap(weatherStations);
        clearMap(incidentMarkers);
    }
}

function onZoom() {
    var zoom = map.getZoom();
    getMapInfo();
    if (zoom < 6 || zoom > 16) {
        traffic.hide();
    } else if ($("#traffic")[0].checked) {
        traffic.show();
    }
}

function turnOfInfo(markersArray) {
    clearMap(markersArray);
    refreshKey = null;
}

function showPopup(selector, ev) {
    $(selector).show();
    $(".popup_bg").show();
}

function hidePopup() {
    active_asset = null;
    clearPopupFields();
    $(".popup").hide();
    $(".popup_bg").hide();
}

function getStandardDegreeName(temperature){
    if(temperature < 0)   return 'coldest';
    if(temperature < 10)  return 'colder';
    if(temperature < 20)  return 'cold';
    if(temperature < 30)  return 'coolest';
    if(temperature < 40)  return 'cooler';
    if(temperature < 50)  return 'cool';
    if(temperature < 60)  return 'warm';
    if(temperature < 70)  return 'warmer';
    if(temperature < 80)  return 'warmest';
    if(temperature < 90)  return 'hot';
    if(temperature < 100) return 'hotter';
    if(temperature < 212) return 'hottest';
}

function extend(childObj, parentObj) {
    var Func = function () {};
    Func.prototype = parentObj.prototype;
    childObj.prototype = new Func();
    childObj.prototype.constructor = childObj;
    childObj.superclass = parentObj.prototype;
}

$(function() {
    // Setup INRIX configuration with the right set of credentials (vendorID, vendorToken)
    var configuration = {
        appVersion: '1.0 Prod',
        systemVersion: Inrix.VERSION,
        deviceModel: 'MDK1.0',
        serverUrl: 'http://api.mobile.inrix.com',
        vendorId: '997800480',
        vendorToken: 'dc4b8a72-97aa-4b83-83c5-5a2f4a73ed93'
    };

    // Let the platform to initialize (with default configuration).
    Inrix.init(configuration);
    // Make sure the device is registered
    if (!Inrix.hasConsumerId()) {
        Inrix.deviceRegister({registerError: function() {
            alert('Error device registering');
        }});
    }
    google.maps.event.addListener(map, "bounds_changed", function() {
        getMapInfo();
        google.maps.event.clearListeners(map, 'bounds_changed');
    });
    $("#traffic").on("change", function(ev) {
        ev.target.checked ? traffic.show() : traffic.hide();
    });
    $("#accidents").on("change", function(ev) {
        if (map.getZoom() > 11) {
            ev.target.checked ? getIncidents() : clearMap(incidentMarkers);
        }
    });
    $("#temperature").on("change", function(ev) {
        if (map.getZoom() > 11) {
            ev.target.checked ? getWeather() : turnOfInfo(weatherStations);
        }
    });
    google.maps.event.addListener(map, "dragend", getMapInfo);
    google.maps.event.addListener(map, "zoom_changed", onZoom);
    $("body").on("click", hideMenu);
    $(".assets_list").on("dragstart", function(ev) {
        ev.originalEvent.dataTransfer.setData("action", "create_asset");
        ev.originalEvent.dataTransfer.setData("type", ev.originalEvent.target.dataset.type);
    })
    $(".triggers_list").on("dragstart", function(ev) {
        ev.originalEvent.dataTransfer.setData("action", "trigger_event");
        ev.originalEvent.dataTransfer.setData("type", ev.originalEvent.target.dataset.type);
    });
    $("#map_canvas").on("dragover", function(ev) {
        ev.preventDefault();
    });
    $("#map_canvas").on("drop", function(ev) {
        ev = ev.originalEvent;
        ev.preventDefault();
        var action = ev.dataTransfer.getData("action");
        if (action === "create_asset") {
            var scale = Math.pow(2, map.getZoom());
            var projection = map.getProjection();
            var topLeft = new google.maps.LatLng(
                map.getBounds().getNorthEast().lat(),
                map.getBounds().getSouthWest().lng()
            );

            var topLeftWorldCoordinate = projection.fromLatLngToPoint(topLeft);
            var topLeftPixelCoordinate = new google.maps.Point(
                    topLeftWorldCoordinate.x * scale,
                    topLeftWorldCoordinate.y * scale);
            var point = new google.maps.Point(
                    (ev.clientX - map.getDiv().getBoundingClientRect().left + topLeftPixelCoordinate.x) / scale,
                    (ev.clientY - map.getDiv().getBoundingClientRect().top + topLeftPixelCoordinate.y) /scale
            );
            var location = map.getProjection().fromPointToLatLng(point);
            var marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                title: 'asset' + getUnigueID()
            });
            props = { 
                id: marker.title,
                type: ev.dataTransfer.getData("type")
            };
            $("#asset_popup [data-property='lat']")[0].value = location.lat();
            $("#asset_popup [data-property='lng']")[0].value = location.lng();
            active_asset = null;
            showPopup("#asset_popup");
            markers.push(marker);
            google.maps.event.addListener(marker, "rightclick", function(ev) {
               active_asset = assets.filter(function(asset) { return asset.id === marker.title; })[0];
               displayMenu(ev.Ra)
            });
            google.maps.event.addListener(marker, "dragend", function(ev) {
                var asset = assets.filter(function(asset) { return asset.id === marker.title; })[0],
                    lat = ev.latLng.lat(),
                    lng = ev.latLng.lng();
                if (asset.lat !== lat || asset.lng !== lng) {
                    asset.lat = ev.latLng.lat();
                    asset.lng = ev.latLng.lng();
                    if (asset.triggers.length) {
                        sendAssetUpdateRequest(asset);
                    }
                }
            });
        } else if (action === "trigger_event") {
            var id = ev.target.getAttribute("title"),
                asset;
            if (id && id.indexOf("asset") !== -1) {
                asset = assets.filter(function(a) { return a.id === id; })[0];
                var trigger = asset.triggers.filter(function(trigger) { return trigger.type === ev.dataTransfer.getData("type"); })[0];
                if (trigger) {
                    alert("Triggering event!");
                    var url = "//" + trigger.url + "?sid=" + asset.sid + "&cid=" + trigger.cid;
                    $.ajax({
                        url: url,
                        success: function() { console.log("Successfully triggered."); },
                        error: function() { console.log("Error on triggering event."); }
                    });
                } else {
                    alert("Trigger with chosen event type wasn't found!")
                }
            }
        }
    });
});
