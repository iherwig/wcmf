define([
    "dojo/_base/declare",
    "dojo/cookie",
    "dojo/json"
], function(
    declare,
    cookie,
    json
) {
    var Session = declare(null, {

        name: appConfig.title.replace(/\s/g, '_'),

        set: function(name, value) {
            var data = this.getAll();
            data[name] = value;
            cookie(this.name, JSON.stringify(data), { path: '/' });
        },

        get: function(name) {
            var data = this.getAll();
            return data[name];
        },

        getAll: function() {
            var cookieValue = cookie(this.name) || '{}';
            return JSON.parse(cookieValue, true);
        }
    });

    return new Session();
});
