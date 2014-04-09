define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../_include/_PageMixin",
    "elfinder/jquery/jquery-1.8.1.min",
    "elfinder/jquery/jquery-ui-1.8.23.custom.min",
    "elfinder/js/elfinder.min",
    "../../locale/Dictionary",
    "dojo/text!./template/BrowsePage.html",
    "xstyle/css!elfinder/jquery/ui-themes/smoothness-1.8.23/jquery-ui-1.8.23.custom.css",
    "xstyle/css!elfinder/css/elfinder.min.css",
    "dojo/domReady!"
], function (
    require,
    declare,
    lang,
    _Page,
    jQuery,
    jQueryUi,
    elFinder,
    Dict,
    template
) {
    return declare([_Page], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Media'),

        postCreate: function() {
            this.inherited(arguments);

            var directory = this.request.getQueryParam("directory");
            var config = {
                lang: appConfig.defaultLanguage,
                url: appConfig.backendUrl+'?action=browseMedia&directory='+directory+"&XDEBUG_SESSION_START=netbeans-xdebug",
                height: 658,
                rememberLastDir: false,
                resizable: false,
                getFileCallback: lang.hitch(this, function(file) {
                    this.onItemClick(file);
                })
            };
            $("#elfinder").elfinder(config).elfinder('instance');
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
            item = decodeURIComponent(item);
            return appConfig.mediaBasePath+item.replace(appConfig.mediaBaseUrl, '');
        }
    });
});