function Trigger(properties) {
    var _self = this;
    
    this.setProperties = function(properties) {
        _self.cid = properties.cid;
        _self.rwp = properties.rwp;
        _self.type = properties.type;
        _self.url = properties.url;
        _self.asset_id = properties.asset_id;
    };
    
    this.getData = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0];
        return data = {
            asset_id: _self.asset_id,
            center: asset.lat + "|" + asset.lng,
            callbackURL: _self.url,
            eventType: _self.type
        };
    }
    
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
    }
    
    this.setProperties(properties);
}
extend(AccidentTrigger, Trigger);

function FlowTrigger(properties) {
    FlowTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.speedUnder = properties.speedUnder;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.speedUnder = _self.speedUnder;
        data.radius = _self.radius;
        return data;
    }
    
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
    this.getData = function() {
        var asset = assets.filter(function(a) { return a.id === _self.asset_id; })[0],
            campaigns = [],
            i, length;
        for (i = 0, length = _self.url.length; i < length; i++) {
            campaigns.push({
                count: _self.count[i],
                callbackURL: _self.url[i]
            });
        }
        return {
            asset_id: _self.asset_id,
            eventType: _self.type,
            retriggerPeriod: _self.rwp,
            hashtag: _self.hashtag,
            campaigns: JSON.stringify(campaigns)
        };
    }
    
    this.setProperties(properties);   
}
extend(TwitterTrigger, Trigger);

function TemperatureTrigger(properties) {
    TemperatureTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.threshold = properties.threshold;
        _self.temperature = properties.temperature;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.threshold = _self.threshold;
        data.temperature = _self.temperature;
        data.radius = _self.radius;
        return data;
    }
    
    this.setProperties(properties);  
}
extend(TemperatureTrigger, Trigger);

function WeatherEventTrigger(properties) {
    WeatherEventTrigger.superclass.constructor.apply(this, arguments);
    
    var _self = this;
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        _self.storm = properties.storm;
        _self.stormSeverity = properties.severity;
        _self.rain = properties.rain;
        _self.snow = properties.snow;
        _self.sunny = properties.sunny;
        _self.thunderStorm = properties.thunderStorm;
        _self.windy = properties.windy;
        _self.windSpeed = properties.windSpeed;
        _self.radius = properties.radius;
    };
    
    var superclass_getData = this.getData;
    this.getData = function() {
        var data = superclass_getData();
        data.storm = _self.storm;
        data.stormSeverity = _self.stormSeverity;
        data.rain = _self.rain;
        data.snow = _self.snow;
        data.sunny = _self.sunny;
        data.thunderStorm = _self.thunderStorm;
        data.windy = _self.windy;
        data.windSpeed = _self.windSpeed;
        data.radius = _self.radius;
        return data;
    }
    
    this.setProperties(properties);
}
extend(WeatherEventTrigger, Trigger);
