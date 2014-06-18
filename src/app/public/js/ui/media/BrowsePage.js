var elFinder = {};

define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dijit/registry",
    "../_include/_PageMixin",
    "elfinder/jquery/jquery-1.9.1.min",
    "elfinder/jquery/jquery-ui-1.10.1.custom.min",
    "../../config/elfinder_config",

    "elfinder/js/elFinder",
    "elfinder/js/elFinder.version",
    "elfinder/js/jquery.elfinder",
    "elfinder/js/elFinder.resources",
    "elfinder/js/elFinder.options",
    "elfinder/js/elFinder.history",
    "elfinder/js/elFinder.command",
    "elfinder/js/ui/overlay",
    "elfinder/js/ui/workzone",
    "elfinder/js/ui/navbar",
    "elfinder/js/ui/dialog",
    "elfinder/js/ui/tree",
    "elfinder/js/ui/cwd",
    "elfinder/js/ui/toolbar",
    "elfinder/js/ui/button",
    "elfinder/js/ui/uploadButton",
    "elfinder/js/ui/viewbutton",
    "elfinder/js/ui/searchbutton",
    "elfinder/js/ui/sortbutton",
    "elfinder/js/ui/panel",
    "elfinder/js/ui/contextmenu",
    "elfinder/js/ui/path",
    "elfinder/js/ui/stat",
    "elfinder/js/ui/places",
    "elfinder/js/commands/back",
    "elfinder/js/commands/forward",
    "elfinder/js/commands/reload",
    "elfinder/js/commands/up",
    "elfinder/js/commands/home",
    "elfinder/js/commands/copy",
    "elfinder/js/commands/cut",
    "elfinder/js/commands/paste",
    "elfinder/js/commands/open",
    "elfinder/js/commands/rm",
    "elfinder/js/commands/info",
    "elfinder/js/commands/duplicate",
    "elfinder/js/commands/rename",
    "elfinder/js/commands/help",
    "elfinder/js/commands/getfile",
    "elfinder/js/commands/mkdir",
    "elfinder/js/commands/mkfile",
    "elfinder/js/commands/upload",
    "elfinder/js/commands/download",
    "elfinder/js/commands/edit",
    "elfinder/js/commands/quicklook",
    "elfinder/js/commands/quicklook.plugins",
    "elfinder/js/commands/extract",
    "elfinder/js/commands/archive",
    "elfinder/js/commands/search",
    "elfinder/js/commands/view",
    "elfinder/js/commands/resize",
    "elfinder/js/commands/sort",
    "elfinder/js/commands/netmount",
    "elfinder/js/i18n/elfinder.en",
    "elfinder/js/i18n/elfinder.de",
    "elfinder/js/jquery.dialogelfinder",

    "dijit/layout/TabContainer",
    "dijit/layout/ContentPane",
    "../../locale/Dictionary",
    "dojo/text!./template/BrowsePage.html",
    "xstyle/css!elfinder/jquery/ui-themes/smoothness/jquery-ui-1.10.1.custom.css",
    "xstyle/css!elfinder/css/common.css",
    "xstyle/css!elfinder/css/dialog.css",
    "xstyle/css!elfinder/css/toolbar.css",
    "xstyle/css!elfinder/css/navbar.css",
    "xstyle/css!elfinder/css/statusbar.css",
    "xstyle/css!elfinder/css/contextmenu.css",
    "xstyle/css!elfinder/css/cwd.css",
    "xstyle/css!elfinder/css/quicklook.css",
    "xstyle/css!elfinder/css/commands.css",
    "xstyle/css!elfinder/css/fonts.css",
    "xstyle/css!elfinder/css/theme.css",
    "dojo/domReady!"
], function (
    require,
    declare,
    lang,
    registry,
    _Page,
    jQuery,
    jQueryUi,
    elFinderConfig,

    elFinder,
    elFinderVersion,
    jqueryElfinder,
    elFinderResources,
    elFinderOptions,
    elFinderHistory,
    elFinderCommand,
    ui_overlay,
    ui_workzone,
    ui_navbar,
    ui_dialog,
    ui_tree,
    ui_cwd,
    ui_toolbar,
    ui_button,
    ui_uploadButton,
    ui_viewbutton,
    ui_searchbutton,
    ui_sortbutton,
    ui_panel,
    ui_contextmenu,
    ui_path,
    ui_stat,
    ui_places,
    commands_back,
    commands_forward,
    commands_reload,
    commands_up,
    commands_home,
    commands_copy,
    commands_cut,
    commands_paste,
    commands_open,
    commands_rm,
    commands_info,
    commands_duplicate,
    commands_rename,
    commands_help,
    commands_getfile,
    commands_mkdir,
    commands_mkfile,
    commands_upload,
    commands_download,
    commands_edit,
    commands_quicklook,
    commands_quicklookPlugins,
    commands_extract,
    commands_archive,
    commands_search,
    commands_view,
    commands_resize,
    commands_sort,
    commands_netmount,
    i18n_elfinderEn,
    i18n_elfinderDe,
    jqueryDialogelfinder,

    TabContainer,
    ContentPane,
    Dict,
    template
) {
    return declare([_Page], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Media'),

        postCreate: function() {
            this.inherited(arguments);

            // tab navigation
            registry.byId("tabContainer").watch("selectedChildWidget", lang.hitch(this, function(name, oval, nval){
                if (nval.id === "contentTab") {
                    window.location.href = appConfig.pathPrefix+'/link?'+this.request.getQueryString();
                }
            }));

            var directory = this.request.getQueryParam("directory");
            lang.mixin(elfinderConfig, {
                lang: appConfig.uiLanguage,
                url: appConfig.backendUrl+'?action=browseMedia&directory='+directory,
                rememberLastDir: false,
                resizable: false,
                getFileCallback: lang.hitch(this, function(file) {
                    this.onItemClick(file);
                })
            });

            setTimeout(function() {
                $("#elfinder").elfinder(elfinderConfig).elfinder('instance');
            }, 500);
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