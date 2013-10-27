define([
    "dojo/_base/declare",
    "dojo/cookie",
    "dojo/json"
], function(
    declare,
    cookie,
    json
) {
    var Cookie = declare(null, {

        name: appConfig.title.replace(/\s/g, '_'),

        set: function(name, value) {
            var data = this.getAll();
            data[name] = value;
            cookie(this.name, JSON.stringify(data), { path: '/' });
        },

        get: function(name, defaultValue) {
            var data = this.getAll();
            if (data[name] === undefined) {
                data[name] = defaultValue;
            }
            return data[name];
        },

        getAll: function() {
            var cookieValue = cookie(this.name) || '{}';
            return JSON.parse(cookieValue, true);
        },

        destroy: function() {
            cookie(this.name, '', { path: '/' });
        }
    });

    return new Cookie();
});
