function Trigger(properties) {
    var _self = this;
    
    this.setProperties = function(properties) {
        _self.cid = properties.cid;
        _self.rwp = properties.rwp;
        _self.type = properties.type;
        _self.asset_id = properties.asset_id;
    };
    
    this.getURL = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0];
        return "http://pluto.signage.me/WebService/sendCommand.ashx?i_user=trgralert&i_password=123&i_stationId=" +
               asset.sid + "&i_command=event&i_param1=" + _self.cid + "&i_param2=&callback=?onSendCommand";
    };
    
    this.getData = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0];
        return {
            assetId: _self.asset_id,
            center: asset.lat + "|" + asset.lng,
            callbackURL: _self.getURL(),
            eventType: _self.type
        };
    };
    
    this.setProperties(properties);
}

function AccidentTrigger(properties) {
    AccidentTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.severity = properties.severity;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.severity = _self.severity;
        data.radius = _self.radius;
        return data;
    };
    
    this.setProperties(properties);
}
extend(AccidentTrigger, Trigger);

function FlowTrigger(properties) {
    FlowTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.condition = properties.condition;
        _self.threshold = properties.threshold;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.condition = _self.condition;
        data.threshold = _self.threshold;
        data.radius = _self.radius;
        return data;
    };
    
    this.setProperties(properties);
}
extend(FlowTrigger, Trigger);

function TwitterTrigger(properties) {
    TwitterTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.hashtag = properties.hashtag;
        _self.count = properties.count;
    };
    
    this.getURL = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0],
            url = [],
            i, length;
        for (i = 0, length = _self.cid.length; i < length; i ++) {
            url.push("http://pluto.signage.me/WebService/sendCommand.ashx?i_user=trgralert&i_password=123&i_stationId=" + 
                     asset.sid + "&i_command=event&i_param1=" + _self.cid[i] + "&i_param2=&callback=?onSendCommand");
        }
        return url;
    };
    
    this.getData = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0],
            url = _self.getURL(),
            campaigns = {},
            i, length;
        for (i = 0, length = url.length; i < length; i++) {
            campaigns[_self.count[i]] = url[i];
        }
        return {
            assetId: _self.asset_id,
            eventType: _self.type,
            retriggerPeriod: _self.rwp,
            hashtag: _self.hashtag,
            campaigns: JSON.stringify(campaigns)
        };
    };
    
    this.setProperties(properties);   
}
extend(TwitterTrigger, Trigger);

function TemperatureTrigger(properties) {
    TemperatureTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.condition = properties.condition;
        _self.threshold = properties.threshold;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.condition = _self.condition;
        data.threshold = _self.threshold;
        data.radius = _self.radius;
        return data;
    };
    
    this.setProperties(properties);  
}
extend(TemperatureTrigger, Trigger);

function WeatherEventTrigger(properties) {
    WeatherEventTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.radius = properties.radius;
        if (properties.windSpeed) {
            _self.windSpeed = properties.windSpeed;
        } else if (_self.windSpeed) {
            delete _self.windSpeed;
        }
        if (properties.stormSeverity) {
            _self.stormSeverity = properties.stormSeverity;
        } else if (_self.stormSeverity) {
            delete _self.stormSeverity;
        }
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.radius = _self.radius;
        if (_self.stormSeverity) {
            data.stormSeverity = _self.stormSeverity;
        }
        if (_self.windSpeed) {
            data.speed = _self.windSpeed;
        }
        return data;
    };
    
    this.setProperties(properties);
}
extend(WeatherEventTrigger, Trigger);
