function Asset(properties) {
    this.setProperties = function(properties) {
        this.id = properties.id;
        this.type = properties.type;
        this.name = properties.name;
        this.description = properties.description;
        this.capPeriod = properties.capPeriod;
        this.capUnit = properties.capUnit;
        this.sid = properties.sid;
        this.lat = properties.lat;
        this.lng = properties.lng;
        this.triggers = [];
    };
    
    this.setProperties(properties);
};