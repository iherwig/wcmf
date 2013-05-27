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
        entityList: {
            schema: p + '/data/:type',
            widget: mid('./ui/data/EntityListPage'),
            layers: l.data || []
        },
        entity: {
            schema: p + '/data/:type/:id',
            widget: mid('./ui/data/EntityPage'),
            layers: l.data || []
        },
        media: {
            schema: p + '/media',
            widget: mid('./ui/media/BrowsePage'),
            layers: l.data || []
        },
        admin: {
            schema: p + '/admin',
            widget: mid('./ui/admin/AdminPage'),
            layers: l.admin || []
        },
    };
});