// Setup Google Maps layer
var mapOptions = {
    center: new google.maps.LatLng(40.7560341, -73.9869242),
    zoom: 12,
    mapTypeId: google.maps.MapTypeId.ROADMAP
};
var map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
var infoWindow = new google.maps.InfoWindow({ content: "", disableAutoPan: true, maxWidth: 250 });
var markers = [];
var incidentMarkers = [];
var weatherStations = [];
var assets = [];
var props;
var active_asset;
var active_popup;
var trigger_type;
var refreshKey;
var updateMap;
var contextMenuIsOpen;
var markerDrag;

var EVENT_TYPES = {
    INCIDENT: "traffic_accident",
    FLOW: "traffic_flow",
    TEMPERATURE: "weather_temperature",
    WEATHER_EVENT: "weather_event",
    WIND: "weather_wind",
    RAIN: "weather_rain",
    SUN: "weather_sun",
    SNOW: "weather_snow",
    THUNDER_STORM: "weather_thunder_storm",
    STORM: "weather_storm",
    TWITTER: "twitter_hash_tag"
};

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
        count = {},
        i, length, trigger, property;
    trigger_type = data.type;
    trigger = active_asset.triggers.filter(function(trigger) { return checkEventType(trigger.type) === trigger_type; })[0];
    if (trigger) {
        if (trigger.type === EVENT_TYPES.TWITTER && trigger.cid.length > 1) {
            for (i = 0, length = trigger.cid.length; i < length - 1; i++) {
                addTwitterFields();
            }
        }
        $(data.popup + " .prop").toArray().forEach(function(el) {
            if (!el.disabled) {
                property = el.dataset.property;
                if ($(el).hasClass("array-element")) {
                    if (!count[property]) {
                        count[property] = 0;
                    }
                    el.value = trigger[property][count[property]];
                    count[property]++;
                } else {
                    if (el.type === "radio") {
                        el.checked = (el.value === trigger[el.dataset.property]);
                    } else {
                        el.value = trigger[property];
                    }
                }
            }
        });
    }
    showPopup(data.popup);
}

function showPropertiesPopup() {
    $("#asset_popup .prop").toArray().forEach(function(el) {
        if (el.type === "radio") {
            el.checked = (el.value === active_asset[el.dataset.property]);
        } else {
            el.value =  active_asset[el.dataset.property];
        }
    });
    showPopup("#asset_popup");
}

function clearPopupFields() {
    $('.popup .error').hide();
    $('.popup .additional').remove();
    $('.popup .prop').toArray().forEach(function(el) {
        switch (el.type) {
            case "number":
            case "text":
                if (el.dataset.default) {
                    el.value = el.dataset.default;
                } else {
                    el.value = "";
                }
                break;
            case "url":
                el.value = "";
                break;
            case "checkbox":
                el.checked = false;
                break;
            case "select-one":
                el.options.selectedIndex = 0;
                break;
            case "radio":
                el.checked = el.dataset.default ? true : false;
                break;
        }
    });
}

function sendAJAX(url, data, callback, errorCallback) {
    $.ajax({
        url: url,
        type: "POST",
        data: data,
        success: callback,
        error: errorCallback || function(error) {
            console.log(error.statusText + ": " + error.responseText);
            alert(error.statusText);
        }
    });
}

function sendAssetUpdateRequest(asset, properties, updatedSID) {
    var data = {
            assetId: asset.id,
            center: properties.lat + "|" + properties.lng
        },
        events, url, i, length;
    if (updatedSID) {
        events = {};
        asset.triggers.forEach(function(trigger) {
            if (trigger.type === EVENT_TYPES.TWITTER) {
                url = trigger.getURL(properties.sid, trigger.cid);
                events[trigger.type] = {};
                for (i = 0, length = url.length; i < length; i++) {
                    events[trigger.type][trigger.count[i]] = url[i];
                }
            } else {
                events[trigger.type] = trigger.getURL(properties.sid, trigger.cid);
            }
        });
        data.events = JSON.stringify(events);
    }
    sendAJAX("/demo/Default/UpdateAsset", data, function(data) {
        var response = JSON.parse(data),
            marker = markers.filter(function(m) { return m.title === asset.id; })[0];
        if (response.status) {
            asset.setProperties(properties);
            console.log(response.message);
        } else {
            marker.setPosition(new google.maps.LatLng(asset.lat, asset.lng));
            console.log(response.errorMessage);
            alert(response.errorMessage);
        }
    },
    function(error) {
        var marker = markers.filter(function(m) { return m.title === asset.id; })[0];
        marker.setPosition(new google.maps.LatLng(asset.lat, asset.lng));
        console.log(error.statusText + ": " + error.responseText);
        alert(error.statusText);
    });
}

function updateAsset() {
    var validateObj = validate(active_popup),
        marker, id, type, properties, lat, lng, sid;
        
    if (validateObj.isValid) {
        id = active_asset? active_asset.id : props.id;
        type = active_asset? active_asset.type : props.type;
        properties = getProperties("#asset_popup");
        properties.id = id;
        properties.type = type;
        marker = markers.filter(function(m) { return m.title === properties.id; })[0];
        marker.setPosition(new google.maps.LatLng(properties.lat, properties.lng));
        if (active_asset) {
            if ((active_asset.lat !== properties.lat || active_asset.lng !== properties.lng || active_asset.sid !== properties.sid) && active_asset.triggers.length) {
                sendAssetUpdateRequest(active_asset, properties, active_asset.sid !== properties.sid);
            } else {
                active_asset.setProperties(properties);
            }
        } else {
            assets.push(new Asset(properties));
        }
        hidePopup();
    } else {
        $(active_popup + " .error").text(validateObj.errorMessage);
        $(active_popup + " .error").show();
    }
}

function removeAsset(asset) {
    var marker;
    hideMenu();
    if (asset.triggers.length) {
        if (confirm("There Are Events Attached To This Asset.\nAre You Sure You Want To Remove This Asset?")) {
            sendAJAX("/demo/Default/DeleteAsset", { assetId: asset.id }, function(data) {
                var response = JSON.parse(data);
                if (response.status) {
                    marker = markers.filter(function(m) { return m.title === asset.id; })[0];
                    marker.setMap(null);
                    markers.splice(markers.indexOf(marker), 1);
                    assets.splice(assets.indexOf(asset), 1);
                    console.log(response.message);
                } else {
                    console.log(response.errorMessage);
                    alert(response.errorMessage);
                }
            });
        }
    } else if (confirm("Are You Sure You Want To Remove This Asset?")) {
        marker = markers.filter(function(m) { return m.title === asset.id; })[0];
        marker.setMap(null);
        markers.splice(markers.indexOf(marker), 1);
        assets.splice(assets.indexOf(asset), 1);
    }
}

function sendTriggerUpdateRequest(asset, trigger, properties, isNewTrigger) {
    var url = isNewTrigger ? "/demo/Default/RegisterEvent" : "/demo/Default/UpdateEvent";
    sendAJAX(url, trigger.getData(properties), function(data) {
        var response = JSON.parse(data);
        if (response.status) {
            if (isNewTrigger) {
                asset.triggers.push(trigger);
            } else {
                trigger.setProperties(properties);
            }
            console.log(response.message);
        } else {
            console.log(response.errorMessage);
            alert(response.errorMessage);
        }
    });
}

function onTriggerRemove(event) {
    var type = event.target.parentNode.dataset.type;
    hideMenu();
    if (confirm("Are You Sure You Want To Remove This Trigger?")) {
        removeTrigger(active_asset, type);
    }
    event.stopPropagation();
}

function removeTrigger(asset, eventType) {
    var trigger = asset.triggers.filter(function(tr) { return checkEventType(tr.type) === eventType; })[0];
    data = {
        assetId: asset.id,
        eventType: trigger.type
    };
    sendAJAX("/demo/Default/DeleteEvent", data, function(data) {
        var response = JSON.parse(data);
        if (response.status) {
            asset.triggers.splice(asset.triggers.indexOf(trigger), 1);
            console.log(response.message);
        } else {
            console.log(response.errorMessage);
            alert(response.errorMessage);
        }
    });
}

function updateTrigger() {
    var validateObj = validate(active_popup),
        properties,
        trigger;
    if (validateObj.isValid) {
        properties = getProperties(active_popup);
        properties.type = properties.type || trigger_type;
        properties.asset_id = active_asset.id;
        trigger = active_asset.triggers.filter(function(trigger) { return checkEventType(trigger.type) === trigger_type; })[0];
        if (trigger) {
            if (checkEventType(trigger.type) === EVENT_TYPES.WEATHER_EVENT && properties.type !== trigger.type) {
                properties.removePrevious = true;
            }
            //change trigger properties
            sendTriggerUpdateRequest(active_asset, trigger, properties, false);
        } else {
            //create trigger
            switch (trigger_type) {
                case EVENT_TYPES.INCIDENT:
                    trigger = new AccidentTrigger(properties);
                    break;
                case EVENT_TYPES.FLOW:
                    trigger = new FlowTrigger(properties);
                    break;
                case EVENT_TYPES.WEATHER_EVENT:
                    trigger = new WeatherEventTrigger(properties);
                    break;
                case EVENT_TYPES.TEMPERATURE:
                    trigger = new TemperatureTrigger(properties);
                    break;
                case EVENT_TYPES.TWITTER:
                    trigger = new TwitterTrigger(properties);
                    break;
            }
            sendTriggerUpdateRequest(active_asset, trigger, properties, true);
        }   
        hidePopup();
    } else {
        $(active_popup + " .error").text(validateObj.errorMessage);
        $(active_popup + " .error").show();
    }
}

function validate(popup) {
    var inputs = $(popup + " .prop").toArray(),
        value, el, errorMessage, i, length, min, max, ascending = {};
    for (i = 0, length = inputs.length; i < length; i++) {
        el = inputs[i];
        if (!el.disabled) {
            value = el.value;
            if (el.required && /^\s*$/.test(value)) {
                errorMessage = el.parentNode.firstElementChild.textContent + " field is required"; 
                return {
                    isValid: false,
                    errorMessage: errorMessage
                };
            }
            switch (el.dataset.type) {
                case "int":
                    if (!/^\s*$/.test(value) && !/^-?\d+$/.test(value)) {
                        errorMessage = el.parentNode.firstElementChild.textContent + " field should contain an integer number";
                        return {
                            isValid: false,
                            errorMessage: errorMessage
                        };
                    }
                    break;
                case "float":
                    if (!/^\s*$/.test(value) && !/^-?\d+(\.\d+)?$/.test(value)) {
                        errorMessage = el.parentNode.firstElementChild.textContent + " field should contain a number";
                        return {
                            isValid: false,
                            errorMessage: errorMessage
                        };
                    }
                    break;
                case "url":
                    if (!/^\s*$/.test(value) && !el.validity.valid) {
                        errorMessage = "Incorrect URL";
                        return {
                            isValid: false,
                            errorMessage: errorMessage
                        };
                    }
                    break;
                case "hashtag":
                    if (!/^#/.test(value)) {
                        errorMessage = "Incorrect hashtag (hashtag should start with '#')";
                        return {
                            isValid: false,
                            errorMessage: errorMessage
                        };
                    }
                    break;
                case "username":
                    if (!/^@/.test(value)) {
                            errorMessage = "Incorrect username (username should start with '@')";
                            return {
                                isValid: false,
                                errorMessage: errorMessage
                            };
                        }
                    break;
            }
            min = el.getAttribute("min");
            max = el.getAttribute("max");
            if (min || max) {
                if (min && max && (value < parseInt(min) || value > parseInt(max))) {
                    errorMessage = el.parentNode.firstElementChild.textContent + " field should contain a number between " + min + " and " + max;
                    return {
                        isValid: false,
                        errorMessage: errorMessage
                    };
                }
                if (min && (value < parseInt(min))) {
                    errorMessage = el.parentNode.firstElementChild.textContent + " field should contain a number equals or greater than " + min;
                    return {
                        isValid: false,
                        errorMessage: errorMessage
                    };
                }
                if (max && (value > parseInt(max))) {
                    errorMessage = el.parentNode.firstElementChild.textContent + " field should contain a number equals or less than " + max;
                    return {
                        isValid: false,
                        errorMessage: errorMessage
                    };
                }
            }
            if ($(el).hasClass("array-element") && $(el).hasClass("ascending")) {
                if (ascending[el.dataset.property] && (getValue(el) <= ascending[el.dataset.property])) {
                    errorMessage = el.parentNode.firstElementChild.textContent + ": each next field should consist a value greater than previous.";
                    return {
                        isValid: false,
                        errorMessage: errorMessage
                    };
                }
                ascending[el.dataset.property] = getValue(el);
            }
        }        
    }
    if ($(popup + ' input[type=radio]').size() && !$(popup + ' input[type=radio]:checked').size()) {
        errorMessage = "One of options should be selected";
        return {
            isValid: false,
            errorMessage: errorMessage
        };
    }
    return {isValid: true, errorMessage: null};
}

function getValue(input) {
    var value, result;
    switch (input.dataset.type) {
        case "int":
            result = parseInt(input.value);
            value = isNaN(result) ? "" : result;
            break;
        case "float":
            result = parseFloat(input.value);
            value = isNaN(result) ? "" : result;
            break;
        default:
            value = input.value;
            break;
    }
    return value;
}

function getProperties(popup) {  
    var properties = {};
    $(popup + " .prop").toArray().forEach(function(el) {
        if (!el.disabled) {
            if ($(el).hasClass("array-element")) {
                properties[el.dataset.property] = properties[el.dataset.property] || [];
                properties[el.dataset.property].push(getValue(el));
            } else {
                if (el.type === "radio") {
                    if (el.checked) {
                        properties[el.dataset.property] = el.value;
                    }
                } else {
                    properties[el.dataset.property] = getValue(el);
                }
            }
        }
    });
    return properties;
}

function onCancel() {
    var marker;
    if (!active_asset) {
        marker = markers.filter(function(m) { return m.title === props.id; })[0];
        marker.setMap(null);
        markers.splice(markers.indexOf(marker), 1);
    }
    hidePopup();
}

function displayMenu(ev){
    var menu = $("#context_menu")[0];
    contextMenuIsOpen = true;
    infoWindow.close();
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
    menu.style.visibility = "visible";
    ev.preventDefault();
}

function hideMenu() {
    $("#context_menu").css("visibility", "hidden");
    $("#context_menu .cross").hide();
    contextMenuIsOpen = false;
}

function showIncidents(incidents) {
    var marker;
    if (map.getZoom() > 11) {
        clearMap(incidentMarkers);
        incidents.forEach(function(incident) {
            marker = new google.maps.Marker({
                position: new google.maps.LatLng(incident.latitude, incident.longitude),
                map: map,
                icon: 'img/incidentPin@2x-small.png',
                title: 'incident'
            });
            google.maps.event.addListener(marker, "mouseover", function() {
                var description = incident.fullDesc ? incident.fullDesc : "No description.";
                if (!contextMenuIsOpen) {
                    infoWindow.setContent("<div class='infowindow_content'>" + description + "</div>");
                    infoWindow.open(map, this);
                }
            });
            google.maps.event.addListener(marker, "mouseout", function() {
                infoWindow.close();
            });
            incidentMarkers.push(marker);
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
        if (response.status) {
            console.log(response.message);
            try {
                stations = xmlToJSON.parseString(response.weather).Inrix[0].Weather[0].Conditions[0].Station;
                stations.forEach(function(station) {
                    var temperature = station.Current[0].Temperature[0]._attr.actual._value;
                    var degreeClass = getStandardDegreeName(temperature);
                    var location = station._attr.point._value;
                    var lat = parseFloat(location.substring(0, location.indexOf("|")));
                    var lng = parseFloat(location.substring(location.indexOf("|") + 1, location.length));
                    var marker = new MarkerWithLabel({
                        position: new google.maps.LatLng(lat, lng),
                        draggable: false,
                        raiseOnDrag: false,
                        map: map,
                        icon: 'img/x.gif',
                        labelContent: temperature + "\u00B0",
                        labelAnchor: new google.maps.Point(16, 19),
                        labelClass: "weather_station " + degreeClass,
                        title: "weather station"
                    });
                    google.maps.event.addListener(marker, "mouseover", function() {
                        if (!contextMenuIsOpen) {
                            infoWindow.setContent("<p>Station:     " + station._attr.name._value + "</p>" +
                                                  "<p>Elevation:   " + station._attr.elevation._value + " yd</p>" +
                                                  "<p>Humidity:    " + station.Current[0].Humidity[0]._attr.relative._value + "%</p>" +
                                                  "<p>Temperature: " + temperature + "\u00B0F</p>" +
                                                  "<p>Pressure:    " + station.Current[0].Pressure[0]._attr.actual._value + " mbar</p>" +
                                                  "<p>Wind speed:  " + station.Current[0].Wind[0]._attr.speed._value + " mph</p>" +
                                                  "<p>Sky:         " + station.Current[0].Sky[0]._attr.description._value + "</p>"
                                                  );
                             infoWindow.open(map, this);
                        }
                    });
                    google.maps.event.addListener(marker, "mouseout", function() {
                        infoWindow.close();
                    });
                    google.maps.event.addListener(marker, "click", hideMenu);
                    weatherStations.push(marker);
                });
            } catch(err) {
                console.log("Cannot parse response.");
            }
        } else {
            console.log(response.errorMessage);
            alert(response.errorMessage);
        }
        $(".loading").removeClass("weather");
        if ($(".loading")[0].classList.length < 2) {
            $(".loading").hide();
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
            $(".loading").removeClass("incidents");
            if ($(".loading")[0].classList.length < 2) {
                $(".loading").hide();
            }
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
            refreshKey: refreshKey
        };
    sendAJAX("/demo/Default/AjaxGetWeatherInRadius", data, showWeather, function(error) {
        console.log(error.statusText + ": " + error.responseText);
        $(".loading").removeClass("weather");
        if ($(".loading")[0].classList.length < 2) {
            $(".loading").hide();
        }
        alert(error.statusText);
    });
}

function getMapInfo() {
    if (map.getZoom() > 11) {
        if ($("#temperature")[0].checked || $("#accidents")[0].checked) {
            $(".loading").show();
        }
        if ($("#temperature")[0].checked) {
            getWeather();
            $(".loading").addClass("weather");
        }
        if ($("#accidents")[0].checked) {
            getIncidents();
            $(".loading").addClass("incidents");
        }
    } else {
        $(".loading").hide();
        $(".loading").removeClass("weather");
        $(".loading").removeClass("incidents");
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

function showPopup(selector) {
    active_popup = selector;
    $(selector).show();
    $(selector).focus();
    $(".popup_bg").show();
}

function hidePopup() {
    active_asset = null;
    clearPopupFields();
    $(".popup").hide();
    $(".popup_bg").hide();
}

function getStandardDegreeName(temperature){
    if(temperature < 0)   { return 'coldest'; }
    if(temperature < 10)  { return 'colder'; }
    if(temperature < 20)  { return 'cold'; }
    if(temperature < 30)  { return 'coolest'; }
    if(temperature < 40)  { return 'cooler'; }
    if(temperature < 50)  { return 'cool'; }
    if(temperature < 60)  { return 'warm'; }
    if(temperature < 70)  { return 'warmer'; }
    if(temperature < 80)  { return 'warmest'; }
    if(temperature < 90)  { return 'hot'; }
    if(temperature < 100) { return 'hotter'; }
    if(temperature < 212) { return 'hottest'; }
}

function extend(childObj, parentObj) {
    var Func = function () {};
    Func.prototype = parentObj.prototype;
    childObj.prototype = new Func();
    childObj.prototype.constructor = childObj;
    childObj.superclass = parentObj.prototype;
}

function autoUpdateMap() {
    function update() {
        getMapInfo();
        autoUpdateMap();
    }   
    updateMap = setTimeout(update, 60000);
}

function getType(trigger_type) {
    var type;
    switch (trigger_type) {
        case EVENT_TYPES.INCIDENT:
            type = "Traffic Incident";
            break;
        case EVENT_TYPES.FLOW:
            type = "Traffic Flow";
            break;
        case EVENT_TYPES.TEMPERATURE:
            type = "Temperature";
            break;
        case EVENT_TYPES.TWITTER:
            type = "Twitter";
            break;
        case EVENT_TYPES.RAIN:
        case EVENT_TYPES.SNOW:
        case EVENT_TYPES.SUN:
        case EVENT_TYPES.THUNDER_STORM:
        case EVENT_TYPES.WIND:
        case EVENT_TYPES.STORM:
            type = "Weather Event";
            break;
    }
    return type;
}

function checkEventType(type) {
    var eventType;
    if ([EVENT_TYPES.RAIN, EVENT_TYPES.SUN, EVENT_TYPES.SNOW, EVENT_TYPES.THUNDER_STORM, EVENT_TYPES.WIND, EVENT_TYPES.STORM].indexOf(type) !== -1) {
        eventType = EVENT_TYPES.WEATHER_EVENT;
    } else {
        eventType = type;
    }
    return eventType;
}

function addTwitterFields() {
    var content = "<div class='additional'><div class='popup-field'>" +
                  "<span class='popup-label'>Count</span>" +
                  "<input data-property='count' data-type='int' class='textinput prop array-element ascending' type='number' min='1' required>" +
                  "</div><div class='remove-fields cross'onclick='removeContainer(event)'></div>" +
                  "<div class='popup-field'>" +
                  "<span class='popup-label'>Campaign ID</span>" +
                  "<input data-property='cid' class='textinput prop array-element' type='text' required></div></div>";
    $(".add-fields").before(content);
}

function removeContainer(event) {
    $(event.target.parentNode).remove();
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
            alert('Error Device Registering');
        }});
    }
    google.maps.event.addListener(map, "bounds_changed", function() {
        getMapInfo();
        autoUpdateMap();
        google.maps.event.clearListeners(map, 'bounds_changed');
    });
    $("#traffic").on("change", function(ev) {
        ev.target.checked ? traffic.show() : traffic.hide();
    });
    $("#accidents").on("change", function(ev) {
        if (map.getZoom() > 11) {
            if (ev.target.checked) {
                getIncidents();
                $(".loading").show();
                $(".loading").addClass("incidents");
            } else {
                clearMap(incidentMarkers);
                $(".loading").removeClass("incidents");
                if ($(".loading")[0].classList.length < 2) {
                    $(".loading").hide();
                }
            }
        }
    });
    $("#temperature").on("change", function(ev) {
        if (map.getZoom() > 11) {
            if (ev.target.checked) {
                getWeather();
                $(".loading").show();
                $(".loading").addClass("weather");
            } else {
                turnOfInfo(weatherStations);
                $(".loading").removeClass("weather");
                if ($(".loading")[0].classList.length < 2) {
                    $(".loading").hide();
                }
            }
        }
    });
    $("input[type='radio']").on("change", function(ev) {
        var inputs = Array.prototype.slice.call(ev.target.form.elements),
            radiobuttons = inputs.filter(function(el) { return (el.type === "radio" && el !== ev.target); }); 
        radiobuttons.forEach(function(el) {
            $(el.parentNode).find(".subitem input, .subitem select").toArray().forEach(function(input) {
                input.disabled = true;
            });
        });
        $(ev.target.parentNode).find(".subitem input, .subitem select").toArray().forEach(function(input) {
            input.disabled = false;
        });
    });
    google.maps.event.addListener(map, "dragend", function() {
        clearTimeout(updateMap);
        getMapInfo();
        autoUpdateMap();
    });
    google.maps.event.addListener(map, "zoom_changed", function() {
        clearTimeout(updateMap);
        onZoom();
        autoUpdateMap();
        hideMenu();
    });
    google.maps.event.addListener(map, "rightclick", hideMenu);
    google.maps.event.addListener(map, "dragstart", hideMenu);
    $("body").on("click", hideMenu);
    $("body").on("dragstart", hideMenu);
    $("#asset_popup").on("keyup", function(ev) {
        if (ev.keyCode === 13) {
            updateAsset();
        }
        if (ev.keyCode === 27) {
            onCancel();
        }
    });
    $(".trigger_popup").on("keyup", function(ev) {
        if (ev.keyCode === 13) {
            updateTrigger();
        }
        if (ev.keyCode === 27) {
            hidePopup();
        }
    });
    $(".assets_list").on("dragstart", function(ev) {
        ev.originalEvent.dataTransfer.setData("action", "create_asset");
        ev.originalEvent.dataTransfer.setData("type", ev.originalEvent.target.dataset.type);
    });
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
                icon: "img/screen_pin.png",
                title: 'asset' + getUnigueID(),
                zIndex: 1000
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
                hideMenu();
                active_asset = assets.filter(function(asset) { return asset.id === marker.title; })[0];
                active_asset.triggers.map(function(tr) { return  checkEventType(tr.type); }).forEach(function(type) {
                    $("#context_menu [data-type='" + type + "'] .cross").show(); 
                });
                displayMenu(ev.Ra);
            });
            google.maps.event.addListener(marker, "mouseover", function(ev) {
                var asset, content;
                if (!contextMenuIsOpen && !markerDrag) {
                    asset = assets.filter(function(asset) { return asset.id === marker.title; })[0];
                    if (asset.triggers.length) {
                        content = "<div class='infowindow_content'><span class='infowindow_title'>Attached events:</span><ul>";
                        asset.triggers.forEach(function(tr) {
                           content += "<li>" + getType(tr.type) + "</li>"; 
                        });
                        content += "</ul></div>";
                        infoWindow.setContent(content);
                    } else {
                        infoWindow.setContent("No events attached to this asset.");
                    }
                    infoWindow.open(map, this);
                }
            });
            google.maps.event.addListener(marker, "mouseout", function(ev) {
                infoWindow.close();
            });
            google.maps.event.addListener(marker, "dragstart", function(ev) {
                markerDrag = true;
                infoWindow.close();
            });
            google.maps.event.addListener(marker, "dragend", function(ev) {
                var asset = assets.filter(function(asset) { return asset.id === marker.title; })[0],
                    lat = ev.latLng.lat(),
                    lng = ev.latLng.lng();
                markerDrag = false;
                if (asset.lat !== lat || asset.lng !== lng) {
                    if (asset.triggers.length) {
                        sendAssetUpdateRequest(asset, {lat: lat, lng: lng}, false);
                    } else {
                        asset.setProperties({lat: lat, lng: lng});
                    }
                }
            });
            google.maps.event.addListener(marker, "dragstart", hideMenu);
        } else if (action === "trigger_event") {
            var id = ev.target.parentNode.getAttribute("title"),
                asset, trigger, url, type;
            if (id && id.indexOf("asset") !== -1) {
                asset = assets.filter(function(a) { return a.id === id; })[0];
                trigger = asset.triggers.filter(function(trigger) { return trigger.type === ev.dataTransfer.getData("type"); })[0];
                type = getType(ev.dataTransfer.getData("type"));
                if (trigger) {
                    url = (trigger.type === EVENT_TYPES.TWITTER) ? trigger.getURL(asset.sid, trigger.cid)[0] : trigger.getURL(asset.sid, trigger.cid);
                    $.ajax({
                        url: url,
                        success: function() { console.log("Successfully triggered."); },
                        error: function() { console.log("Error on triggering event."); },
                        complete: function() { alert(type + " Trigger Simulated."); }
                    });
                } else {
                    alert(type + " Trigger Was Not Set.");
                }
            }
        }
    });
});