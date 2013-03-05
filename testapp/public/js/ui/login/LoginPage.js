define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "bootstrap/Button",
    "dojo/_base/lang",
    "dojo/dom-attr",
    "dojo/dom-form",
    "dojo/query",
    "dojo/request",
    "dojo/on",
    "app/Error",
    "dojo/text!./template/LoginPage.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    button,
    lang,
    domAttr,
    domForm,
    query,
    request,
    on,
    error,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware], {

        router: null,
        request: null,
        session: null,
        templateString: template,

        constructor: function(params) {
            this.router = params.router;
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle('Login');

            query('a.push', this.domNode).forEach(lang.hitch(this, function(node) {
                var url, route = this.router.getRoute(domAttr.get(node, 'data-dojorama-route')); // valid route name in data-dojo-props attribute of node?
                if (!route) { return; }

                url = route.assemble();
                node.href = url;

                this.own(on(node, 'click', lang.hitch(this, function (ev) {
                    ev.preventDefault();
                    this.push(url);
                })));
            }));
        },

        startup: function() {
            this.inherited(arguments);
        },

        _login: function(e) {
            // prevent the page from navigating after submit
            e.stopPropagation();
            e.preventDefault();

            var data = domForm.toObject('loginForm');
            data.controller = 'wcmf\\application\\controller\\LoginController';
            data.action = 'dologin';
            data.responseFormat = 'json';

            query('.btn').button('loading');
            error.hide();
            request.post('../main.php?XDEBUG_SESSION_START=netbeans-xdebug', {
                data: data,
                handleAs: 'json'

            }).then(function(response){
                query('.btn').button('reset');
                error.show(response.errorMessage);
            });
        }
    });
});