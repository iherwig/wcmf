define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/query",
    "dojo/topic",
    "dojo/_base/window",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "dijit/tree/ObjectStoreModel",
    "dijit/Tree",
    "../../persistence/TreeStore",
    "../../locale/Dictionary",
    "dojo/text!./template/BrowsePage.html",
    "dojo/domReady!"
], function (
    require,
    declare,
    lang,
    dom,
    query,
    topic,
    win,
    _Page,
    _Notification,
    ObjectStoreModel,
    Tree,
    TreeStore,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Content'),

        postCreate: function() {
            this.inherited(arguments);

            var store = TreeStore.getStore();
            var model = new ObjectStoreModel({
                store: store,
                labelAttr: "displayText",
                query: {oid: 'init'}
            });

            var tree = new Tree({
                model: model,
                showRoot: false,
                onClick: lang.hitch(this, function(item) {
                    this.onItemClick(item);
                })
            });
            topic.subscribe("store-error", lang.hitch(this, function(error) {
                this.showBackendError(error);
            }));
            tree.placeAt(dom.byId('resourcetree'));
            tree.startup();
        },

        onItemClick: function(item) {
            if (item.isFolder) {
                return;
            }
            var funcNum = this.request.getQueryParam('CKEditorFuncNum');
            var callback = this.request.getQueryParam('callback');

            var value = 'link://'+this.getItemUrl(item);
            if (window.opener.CKEDITOR && funcNum) {
                window.opener.CKEDITOR.tools.callFunction(funcNum, value, function() {
                    // callback executed in the scope of the button that called the file browser
                    // see: http://docs.ckeditor.com/#!/guide/dev_file_browser_api Example 4
                    //
                    // set the protocoll to 'other'
                    // see: http://ckeditor.com/forums/CKEditor-3.x/Tutorial-how-modify-Links-Plugin-link-cms-pages
                    var dialog = this.getDialog();
                    if (dialog.getName() === 'link') {
                        dialog.setValueOf('info', 'protocol', '');
                    }
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
            return item.oid;
        }
    });
});