define([
    "dojo/_base/declare",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dojomat/_AppAware",
    "dojomat/_StateAware",
    "elfinder/jquery/jquery-1.8.1.min",
    "elfinder/jquery/jquery-ui-1.8.23.custom.min",
    "elfinder/js/elFinder.min",
    "dojo/text!./template/BrowsePage.html",
    "xstyle/css!elfinder/jquery/ui-themes/smoothness-1.8.23/jquery-ui-1.8.23.custom.css",
    "xstyle/css!elfinder/css/elfinder.min.css"
], function (
    declare,
    _WidgetBase,
    _TemplatedMixin,
    _AppAware,
    _StateAware,
    jQuery,
    jQueryUi,
    elFinder,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _AppAware, _StateAware], {

        request: null,
        session: null,
        templateString: template,

        constructor: function(params) {
            this.request = params.request;
            this.session = params.session;
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - Media');

            var funcNum = this.request.getQueryParam('CKEditorFuncNum');

            $("#elfinder").elfinder({
                lang: appConfig.defaultLanguage,
                url: 'main.php?action=browsemedia',
                height: 658,
                resizable: false,
                getFileCallback : function(file) {
                    window.opener.CKEDITOR.tools.callFunction(funcNum, file);
                    window.close();
                }
            }).elfinder('instance');
        }
    });
});