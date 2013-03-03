define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "dojo/text!./template/ErrorPage.html"
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
        error: null,
        templateString: template,

        constructor: function (params) {
            this.router = params.router;
            this.request = params.request;
            this.session = params.session;
            this.error = params.error;
        },

        postCreate: function () {
            this.inherited(arguments);
            this.setTitle('An error has occured');
            this.messageNode.innerHTML = this.error.message;
        },

        startup: function () {
            this.inherited(arguments);
        }
    });
});