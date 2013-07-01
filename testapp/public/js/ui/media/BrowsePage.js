define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "elfinder/jquery/jquery-1.8.1.min",
    "elfinder/jquery/jquery-ui-1.8.23.custom.min",
    "elfinder/js/elFinder.min",
    "../../locale/Dictionary",
    "dojo/text!./template/BrowsePage.html",
    "xstyle/css!elfinder/jquery/ui-themes/smoothness-1.8.23/jquery-ui-1.8.23.custom.css",
    "xstyle/css!elfinder/css/elfinder.min.css",
    "dojo/domReady!"
], function (
    require,
    declare,
    lang,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    jQuery,
    jQueryUi,
    elFinder,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Media'));

            $("#elfinder").elfinder({
                lang: appConfig.defaultLanguage,
                url: appConfig.backendUrl+'?action=browseMedia',
                height: 658,
                resizable: false,
                getFileCallback: lang.hitch(this, function(file) {
                    this.onItemClick(file);
                })
            }).elfinder('instance');
        },

        onItemClick: function(item) {
            var funcNum = this.request.getQueryParam('CKEditorFuncNum');
            var callback = this.request.getQueryParam('callback');

            var value = this.getItemUrl(item);
            if (window.opener.CKEDITOR && funcNum) {
                window.opener.CKEDITOR.tools.callFunction(funcNum, value);
            }
            else if (callback) {
                if (window.opener[callback]) {
                    window.opener[callback](value);
                }
            }
            window.close();
        },

        getItemUrl: function(item) {
            return appConfig.mediaBasePath+item.replace(appConfig.mediaBaseUrl, '');
        }
    });
});