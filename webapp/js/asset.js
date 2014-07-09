function Asset(properties) {
    var _self = this;
    
    this.setProperties = function(properties) {
        Object.keys(properties).forEach(function(prop) {
           _self[prop] = properties[prop]; 
        });
    };
    
    this.triggers = [];
    this.setProperties(properties);
}