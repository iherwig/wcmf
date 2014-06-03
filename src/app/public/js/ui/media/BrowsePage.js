define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../_include/_PageMixin",
    "elfinder/jquery/jquery.min",
    "elfinder/jquery/jquery-ui.min",
    "elfinder/js/elfinder.min",
    "elfinder/js/i18n/elfinder.de",
    "../../locale/Dictionary",
    "dojo/text!./template/BrowsePage.html",
    "xstyle/css!elfinder/jquery/jquery-ui.css",
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
    elFinderDE,
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
                lang: appConfig.uiLanguage,
                url: appConfig.backendUrl+'?action=browseMedia&directory='+directory,
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
                window.opener.CKEDITOR.tools.callFunction(funcNum, value, function() {
                    // callback executed in the scope of the button that called the file browser
                    // see: http://docs.ckeditor.com/#!/guide/dev_file_browser_api Example 4
                });
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