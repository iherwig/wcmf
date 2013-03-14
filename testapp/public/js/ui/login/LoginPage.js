define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "dojo/_base/lang",
    "dojo/dom-form",
    "dojo/query",
    "dojo/request",
    "app/Error",
    "dojo/text!./template/LoginPage.html",
    "bootstrap/Button"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    lang,
    domForm,
    query,
    request,
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
            request.post('../main.php', {
                data: data,
                handleAs: 'json'

            }).then(lang.hitch(this, function(response){
                if (response.errorMessage) {
                    query('.btn').button('reset');
                    error.show(response.errorMessage);
                }
                else {
                    var route = this.router.getRoute('home');
                    var url = route.assemble();
                    this.push(url);
                }
            }));
        }
    });
});