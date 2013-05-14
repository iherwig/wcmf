define(["dojo/_base/config", "require"], function (config, require) {

    var p = config['routing-map'].pathPrefix,
        l = config['routing-map'].layers || {},
        mid = require.toAbsMid
    ;

    return {
        login: {
            schema: p + '',
            widget: mid('./ui/login/LoginPage'),
            layers: l.login || []
        },
        logout: {
            schema: p + '/logout',
            widget: mid('./ui/login/LogoutPage'),
            layers: l.logout || []
        },
        home: {
            schema: p + '/home',
            widget: mid('./ui/home/HomePage'),
            layers: l.home || []
        },
        nodeList: {
            schema: p + '/data/:type',
            widget: mid('./ui/data/ListPage'),
            layers: l.data || []
        },
        node: {
            schema: p + '/data/:type/:id',
            widget: mid('./ui/data/NodePage'),
            layers: l.data || []
        },
        admin: {
            schema: p + '/admin',
            widget: mid('./ui/admin/AdminPage'),
            layers: l.admin || []
        },
    };
});