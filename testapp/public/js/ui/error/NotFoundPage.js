define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "dojo/text!./template/NotFoundPage.html"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware], {

        router: null,
        request: null,
        session: null,
        templateString: template,

        constructor: function (params) {
            this.router = params.router;
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.setTitle('Page not found');
            this.messageNode.innerHTML = 'Page not found';
        },

        startup: function () {
            this.inherited(arguments);
        }
    });
});