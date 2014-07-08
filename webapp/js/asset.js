function Asset(properties) {
    this.setProperties = function(properties) {
        this.id = properties.id;
        this.type = properties.type;
        this.name = properties.name;
        this.sid = properties.sid;
        this.lat = properties.lat;
        this.lng = properties.lng;
        this.maxTriggers = properties.maxTriggers;
        this.platform = properties.platform;
    };
    
    this.triggers = [];
    this.setProperties(properties);
}