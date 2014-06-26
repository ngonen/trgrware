function Trigger(properties) {
    this.setProperties = function(properties) {
        this.cid = properties.cid;
        this.rwp = properties.rwp;
        this.type = properties.type;
    };
    
    this.setProperties(properties);
}

function AccidentTrigger(properties) {
    AccidentTrigger.superclass.constructor.apply(this, arguments);
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        this.severity = properties.severity;
        this.radius = properties.radius;
    };
    
    this.setProperties(properties);
}
extend(AccidentTrigger, Trigger);

function FlowTrigger(properties) {
    FlowTrigger.superclass.constructor.apply(this, arguments);
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        this.speedUnder = properties.speedUnder;
        this.radius = properties.radius;
    };
    
    this.setProperties(properties);
}
extend(FlowTrigger, Trigger);

function TwitterTrigger(properties) {
    TwitterTrigger.superclass.constructor.apply(this, arguments);
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        this.hashtag = properties.hashtag;
    };
    
    this.setProperties(properties);   
}
extend(TwitterTrigger, Trigger);

function TemperatureTrigger(properties) {
    TemperatureTrigger.superclass.constructor.apply(this, arguments);
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        this.threshold = properties.threshold;
        this.temperature = properties.temperature;
        this.radius = properties.radius;
    };
    
    this.setProperties(properties);  
}
extend(TemperatureTrigger, Trigger);

function WeatherEventTrigger(properties) {
    WeatherEventTrigger.superclass.constructor.apply(this, arguments);
    
    var superclass_setProperties = this.setProperties;
    this.setProperties = function(properties) {
        superclass_setProperties(properties);
        this.storm = properties.storm;
        this.stormSeverity = properties.severity;
        this.rain = properties.rain;
        this.snow = properties.snow;
        this.sunny = properties.sunny;
        this.thunderStorm = properties.thunderStorm;
        this.windy = properties.windy;
        this.windSpeed = properties.windSpeed;
        this.radius = properties.radius;
    };
    
    this.setProperties(properties);
}
extend(WeatherEventTrigger, Trigger);
