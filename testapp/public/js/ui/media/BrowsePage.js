define([
    "dojo/_base/declare",
    "dojo/dom",
    "dojo/_base/window",
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
    dom,
    win,
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
            var fieldId = this.request.getQueryParam('fieldId');

            var elFinder = $("#elfinder").elfinder({
                lang: appConfig.defaultLanguage,
                url: 'main.php?action=browsemedia',
                height: 658,
                resizable: false,
                getFileCallback : function(file) {
                    var fileRel = file.replace(appConfig.mediaBase, '');
                    if (window.opener.CKEDITOR && funcNum) {
                        window.opener.CKEDITOR.tools.callFunction(funcNum, file);
                    }
                    else if (fieldId) {
                      console.log(elFinder);
                        win.setContext(window, window.opener.document);
                        dom.byId(fieldId).value = fileRel;
                    }
                    window.close();
                }
            }).elfinder('instance');
        }
    });
});