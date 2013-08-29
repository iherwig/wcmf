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

var profile = {
    basePath: "../public",
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
        "dojo-sync-loader": 0,
        "dojo-test-sniff": 0,
        "dojo-timeout-api": 0,
        "dojo-trace-api": 0,
        "dojo-undef-api": 0,
        "dojo-v1x-i18n-Api": 1,
        "dom": 1,
        "host-browser": 1,
        "extend-dojo": 1
    },

    packages: [{
        name: 'dojo',
        location: 'vendor/dojo/dojo',
        destLocation: 'vendor/dojo/dojo'
    }, {
        name: 'dijit',
        location: 'vendor/dojo/dijit',
        destLocation: 'vendor/dojo/dijit'
    }, {
        name: 'dojox',
        location: 'vendor/dojo/dojox',
        destLocation: 'vendor/dojo/dojox'
    }, {
        name: 'routed',
        location: 'vendor/routed',
        destLocation: 'vendor/routed'
    }, {
        name: 'dojomat',
        location: 'vendor/dojomat',
        destLocation: 'vendor/dojomat'
    }, {
        name: 'dgrid',
        location: 'vendor/dgrid',
        destLocation: 'vendor/dgrid'
    }, {
        name: 'xstyle',
        location: 'vendor/xstyle',
        destLocation: 'vendor/xstyle'
    }, {
        name: 'put-selector',
        location: 'vendor/put-selector',
        destLocation: 'vendor/put-selector'
    }, {
        name: 'ckeditor',
        location: 'vendor/ckeditor',
        destLocation: 'vendor/ckeditor'
    }, {
        name: 'elfinder',
        location: 'vendor/elfinder',
        destLocation: 'vendor/elfinder'
    }, {
        name: 'dbootstrap',
        location: 'vendor/dbootstrap',
        destLocation: 'vendor/dbootstrap'
    }, {
        name: 'font-awesome',
        location: 'vendor/font-awesome',
        destLocation: 'vendor/font-awesome'
    }, {
        name: 'twitter-bootstrap',
        location: 'vendor/twitter-bootstrap',
        destLocation: 'vendor/twitter-bootstrap'
    }, {
        name: 'app',
        location: 'js',
        destLocation: 'js'
    }, {
        name: 'styles',
        location: 'css',
        destLocation: 'css'
    }],

    dirs: [
      ['images', 'images'],
      ['media', 'media', /media\/.+/]
    ],
    files: [
      ['.htaccess', '.htaccess'],
      ['base_dir.php', 'base_dir.php'],
      ['index.php', 'index.php'],
      ['main.php', 'main.php']
    ],

    layers: {
        'app/App': {
            include: [
                "app/App",
                "app/Cookie",
                "app/routing-map",
                "app/action/ActionBase",
                "app/action/Create",
                "app/action/CreateInRelation",
                "app/action/Delete",
                "app/action/Edit",
                "app/action/Link",
                "app/action/Unlink",
                "app/locale/Dictionary",
                "app/model/meta/Model",
                "app/model/meta/Node",
                "app/model/meta/_TypeList",
                "app/persistence/BaseStore",
                "app/persistence/Entity",
                "app/persistence/ListStore",
                "app/persistence/RelationStore",
                "app/persistence/Store",
                "app/persistence/TreeStore",
                "app/ui/admin/AdminPage",
                "app/ui/admin/PrincipalListPage",
                "app/ui/admin/PrincipalPage",
                "app/ui/admin/widget/RoleFormWidget",
                "app/ui/admin/widget/UserFormWidget",
                "app/ui/data/EntityListPage",
                "app/ui/data/EntityPage",
                "app/ui/data/display/Renderer",
                "app/ui/data/display/renderer/Image",
                "app/ui/data/display/renderer/Text",
                "app/ui/data/input/Factory",
                "app/ui/data/input/widget/BinaryCheckBox",
                "app/ui/data/input/widget/CheckBox",
                "app/ui/data/input/widget/CKEditor",
                "app/ui/data/input/widget/Date",
                "app/ui/data/input/widget/FileBrowser",
                "app/ui/data/input/widget/LinkBrowser",
                "app/ui/data/input/widget/PasswordBox",
                "app/ui/data/input/widget/RadioButton",
                "app/ui/data/input/widget/SelectBox",
                "app/ui/data/input/widget/TextArea",
                "app/ui/data/input/widget/TextBox",
                "app/ui/data/input/widget/_BinaryItemsControl",
                "app/ui/data/input/widget/_BrowserControl",
                "app/ui/data/widget/EntityFormWidget",
                "app/ui/data/widget/EntityListWidget",
                "app/ui/data/widget/EntityRelationWidget",
                "app/ui/data/widget/EntityTabWidget",
                "app/ui/error/ErrorPage",
                "app/ui/error/NotFoundPage",
                "app/ui/home/HomePage",
                "app/ui/link/BrowsePage",
                "app/ui/login/LoginPage",
                "app/ui/login/LogoutPage",
                "app/ui/media/BrowsePage",
                "app/ui/settings/SettingsPage",
                "app/ui/_include/FormLayout",
                "app/ui/_include/_NotificationMixin",
                "app/ui/_include/_PageMixin",
                "app/ui/_include/widget/Button",
                "app/ui/_include/widget/ConfirmDlgWidget",
                "app/ui/_include/widget/GridWidget",
                "app/ui/_include/widget/NavigationWidget",
                "app/ui/_include/widget/NotificationWidget",
                "app/ui/_include/widget/ObjectSelectDlgWidget",
                "app/ui/_include/widget/PopupDlgWidget"
            ]
        }
    }
};