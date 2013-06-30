define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/query",
    "dojo/_base/window",
    "dijit/_WidgetBase",
    "dijit/_TemplatedMixin",
    "dijit/_WidgetsInTemplateMixin",
    "../_include/_PageMixin",
    "dijit/tree/ObjectStoreModel",
    "dijit/Tree",
    "../../model/meta/Model",
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
    win,
    _WidgetBase,
    _TemplatedMixin,
    _WidgetsInTemplateMixin,
    _Page,
    ObjectStoreModel,
    Tree,
    Model,
    TreeStore,
    Dict,
    template
) {
    return declare([_WidgetBase, _TemplatedMixin, _WidgetsInTemplateMixin, _Page], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(appConfig.title+' - '+Dict.translate('Inhalt'));

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
            tree.placeAt(dom.byId('resourcetree'));
            tree.startup();
        },

        onItemClick: function(item) {
            if (item.isFolder) {
                return;
            }
            var funcNum = this.request.getQueryParam('CKEditorFuncNum');
            var callback = this.request.getQueryParam('callback');

            var value = 'link:'+this.getItemUrl(item);
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
            var route = this.router.getRoute("entity");
            var type = Model.getSimpleTypeName(Model.getTypeNameFromOid(item.oid));
            var id = Model.getIdFromOid(item.oid);
            var pathParams = { type:type, id:id };
            var url = route.assemble(pathParams);
            return url;
        }
    });
});