/**
 * This is the application build profile. While it looks similar, this build profile
 * is different from the package build profile at `app/package.js` in the following ways:
 *
 * 1. you can have multiple application build profiles (e.g. one for desktop, one for tablet, etc.), but only one
 *    package build profile;
 * 2. the package build profile only configures the `resourceTags` for the files in the package, whereas the
 *    application build profile tells the build system how to build the entire application.
 *
 * Look to `util/build/buildControlDefault.js` for more information on available options and their default values.
 *
 * See: http://dojotoolkit.org/reference-guide/1.9/build/buildSystem.html
 */
var appSrc = [
    "app/js/App",
    "app/js/Cookie",
    "app/js/User",
    "app/js/routing-map",
    "app/js/action/ActionBase",
    "app/js/action/Copy",
    "app/js/action/Create",
    "app/js/action/CreateInRelation",
    "app/js/action/Delete",
    "app/js/action/Edit",
    "app/js/action/Link",
    "app/js/action/Lock",
    "app/js/action/Log",
    "app/js/action/Process",
    "app/js/action/Unlink",
    "app/js/locale/Dictionary",
    "app/js/model/meta/Model",
    "app/js/model/meta/Node",
    "app/js/model/meta/_TypeList",
    "app/js/persistence/BackendError",
    "app/js/persistence/BaseStore",
    "app/js/persistence/Entity",
    "app/js/persistence/ListStore",
    "app/js/persistence/RelationStore",
    "app/js/persistence/SearchStore",
    "app/js/persistence/Store",
    "app/js/persistence/TreeStore",
    "app/js/ui/admin/AdminPage",
    "app/js/ui/admin/PrincipalListPage",
    "app/js/ui/admin/PrincipalPage",
    "app/js/ui/admin/widget/RoleFormWidget",
    "app/js/ui/admin/widget/UserFormWidget",
    "app/js/ui/data/EntityListPage",
    "app/js/ui/data/EntityPage",
    "app/js/ui/data/display/Renderer",
    "app/js/ui/data/display/renderer/Image",
    "app/js/ui/data/display/renderer/Text",
    "app/js/ui/data/input/Factory",
    "app/js/ui/data/input/widget/BinaryCheckBox",
    "app/js/ui/data/input/widget/CKEditor",
    "app/js/ui/data/input/widget/CheckBox",
    "app/js/ui/data/input/widget/Color",
    "app/js/ui/data/input/widget/Date",
    "app/js/ui/data/input/widget/FileBrowser",
    "app/js/ui/data/input/widget/LinkBrowser",
    "app/js/ui/data/input/widget/PasswordBox",
    "app/js/ui/data/input/widget/RadioButton",
    "app/js/ui/data/input/widget/SelectBox",
    "app/js/ui/data/input/widget/TextArea",
    "app/js/ui/data/input/widget/TextBox",
    "app/js/ui/data/input/widget/_BinaryItemsControl",
    "app/js/ui/data/input/widget/_BrowserControl",
    "app/js/ui/data/widget/EntityFormWidget",
    "app/js/ui/data/widget/EntityListWidget",
    "app/js/ui/data/widget/EntityRelationWidget",
    "app/js/ui/data/widget/EntityTabWidget",
    "app/js/ui/error/ErrorPage",
    "app/js/ui/error/NotFoundPage",
    "app/js/ui/home/HomePage",
    "app/js/ui/link/BrowsePage",
    "app/js/ui/login/LoginPage",
    "app/js/ui/login/LogoutPage",
    "app/js/ui/media/BrowsePage",
    "app/js/ui/search/SearchPage",
    "app/js/ui/search/SearchResultPage",
    "app/js/ui/settings/SettingsPage",
    "app/js/ui/_include/FormLayout",
    "app/js/ui/_include/_HelpMixin",
    "app/js/ui/_include/_NotificationMixin",
    "app/js/ui/_include/_PageMixin",
    "app/js/ui/_include/widget/Button",
    "app/js/ui/_include/widget/ConfirmDlgWidget",
    "app/js/ui/_include/widget/GridWidget",
    "app/js/ui/_include/widget/NavigationWidget",
    "app/js/ui/_include/widget/NotificationWidget",
    "app/js/ui/_include/widget/ObjectSelectDlgWidget",
    "app/js/ui/_include/widget/PopupDlgWidget",

    "xstyle/core/load-css"
];

var profile = {
    basePath: "../app/public",
    releaseDir: "../release",
    releaseName: "",
    action: "release",

    layerOptimize: "closure",
    optimize: "closure",
    cssOptimize: "comments",
    mini: true,
    stripConsole: "warn",
    selectorEngine: "lite",

    defaultConfig: {
        hasCache:{
            "dojo-built": 1,
            "dojo-loader": 1,
            "dom": 1,
            "host-browser": 1,
            "config-selectorEngine": "lite"
        },
        async: 1
    },

    staticHasFeatures: {
        "config-deferredInstrumentation": 0,
        "config-dojo-loader-catches": 0,
        "config-tlmSiblingOfDojo": 0,
        "dojo-amd-factory-scan": 0,
        "dojo-combo-api": 0,
        "dojo-config-api": 1,
        "dojo-config-require": 0,
        "dojo-debug-messages": 0,
        "dojo-dom-ready-api": 1,
        "dojo-firebug": 0,
        "dojo-guarantee-console": 1,
        "dojo-has-api": 1,
        "dojo-inject-api": 1,
        "dojo-loader": 1,
        "dojo-log-api": 0,
        "dojo-modulePaths": 0,
        "dojo-moduleUrl": 0,
        "dojo-publish-privates": 0,
        "dojo-requirejs-api": 0,
        "dojo-sniff": 0,
        "dojo-sync-loader": 1,
        "dojo-test-sniff": 0,
        "dojo-timeout-api": 0,
        "dojo-trace-api": 0,
        "dojo-undef-api": 0,
        "dojo-v1x-i18n-Api": 1,
        "dom": 1,
        "host-browser": 1,
        "extend-dojo": 1
    },

    packages: [
      { name: 'dojo', location: 'vendor/dojo/dojo', destLocation: 'vendor/dojo/dojo' },
      { name: 'dijit', location: 'vendor/dojo/dijit', destLocation: 'vendor/dojo/dijit' },
      { name: 'dojox', location: 'vendor/dojo/dojox', destLocation: 'vendor/dojo/dojox' },
      { name: 'routed', location: 'vendor/routed', destLocation: 'vendor/routed' },
      { name: 'dojomat', location: 'vendor/dojomat', destLocation: 'vendor/dojomat' },
      { name: 'dgrid', location: 'vendor/dgrid', destLocation: 'vendor/dgrid' },
      { name: 'xstyle', location: 'vendor/xstyle', destLocation: 'vendor/xstyle' },
      { name: 'put-selector', location: 'vendor/put-selector', destLocation: 'vendor/put-selector' },
      { name: 'ckeditor', location: 'vendor/ckeditor', destLocation: 'vendor/ckeditor' },
      { name: 'elfinder', location: 'vendor/elfinder', destLocation: 'vendor/elfinder' },

      { name: 'app', location: '.', destLocation: '.' },
    ],

    dirs: [
      ['images', 'images'],
      ['media', 'media', /media\/.+/]
    ],
    files: [
      ['.htaccess', '.htaccess'],
      ['base_dir.php', 'base_dir.php'],
      ['cli.php', 'cli.php'],
      ['index.php', 'index.php'],
      ['main.php', 'main.php'],
      ['soap.php', 'soap.php']
    ],

    layers: {
        'dojo/dojo': {
            customBase: true
        },
        'app/js/App': {
            include: appSrc
        }
    }
};