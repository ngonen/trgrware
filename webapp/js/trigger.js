function Trigger(properties) {
    var _self = this;
    
    this.setProperties = function(properties) {
        _self.cid = properties.cid;
        _self.rwp = properties.rwp;
        _self.type = properties.type;
        _self.asset_id = properties.asset_id;
    };
    
    this.getURL = function(sid, cid) {
        return "http://pluto.signage.me/WebService/sendCommand.ashx?i_user=trgralert&i_password=123&i_stationId=" +
               sid + "&i_command=event&i_param1=" + cid + "&i_param2=&callback=?onSendCommand";
    };
    
    this.getData = function(properties) {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0];
        return {
            assetId: _self.asset_id,
            center: asset.lat + "|" + asset.lng,
            callbackURL: _self.getURL(asset.sid, properties.cid),
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
    this.getData = function(properties) {
        var data = superclass_getData(properties);
        data.severity = properties.severity;
        data.radius = properties.radius;
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
    this.getData = function(properties) {
        var data = superclass_getData(properties);
        data.condition = properties.condition;
        data.threshold = properties.threshold;
        data.radius = properties.radius;
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
        _self.username = properties.username;
    };
    
    this.getURL = function(sid, cid) {
        var url = [],
            i, length;
        for (i = 0, length = cid.length; i < length; i ++) {
            url.push("http://pluto.signage.me/WebService/sendCommand.ashx?i_user=trgralert&i_password=123&i_stationId=" + 
                     sid + "&i_command=event&i_param1=" + cid[i] + "&i_param2=&callback=?onSendCommand");
        }
        return url;
    };
    
    this.getData = function(properties) {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0],
            url = _self.getURL(asset.sid, properties.cid),
            campaigns = {},
            i, length;
        for (i = 0, length = url.length; i < length; i++) {
            campaigns[properties.count[i]] = url[i];
        }
        return {
            assetId: _self.asset_id,
            eventType: _self.type,
            retriggerPeriod: properties.rwp,
            hashtag: properties.hashtag,
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
    this.getData = function(properties) {
        var data = superclass_getData(properties);
        data.condition = properties.condition;
        data.threshold = properties.threshold;
        data.radius = properties.radius;
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
    this.getData = function(properties) {
        var data = superclass_getData(properties);
        data.radius = properties.radius;
        if (properties.stormSeverity) {
            data.stormSeverity = properties.stormSeverity;
        }
        if (properties.windSpeed) {
            data.speed = properties.windSpeed;
        }
        return data;
    };
    
    this.setProperties(properties);
}
extend(WeatherEventTrigger, Trigger);
