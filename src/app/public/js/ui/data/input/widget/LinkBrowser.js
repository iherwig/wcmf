define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "../../../_include/widget/Button",
    "../../../_include/widget/ConfirmDlgWidget",
    "../../../../model/meta/Model",
    "../../../../locale/Dictionary",
    "./_BrowserControl"
],
function(
    declare,
    lang,
    topic,
    Button,
    ConfirmDlg,
    Model,
    Dict,
    _BrowserControl
) {
    return declare([_BrowserControl], {

        browserUrl: appConfig.pathPrefix+'/link',

        postCreate: function() {
            this.inherited(arguments);

            var testBtn = new Button({
                innerHTML: '<i class="fa fa-external-link"></i>',
                "class": "btn-mini",
                onClick: lang.hitch(this, function() {
                    var route = this.getLinkRoute();
                    if (route) {
                        if (this.isDirty()) {
                            new ConfirmDlg({
                                title: Dict.translate("Confirm Leave Page"),
                                message: Dict.translate("'%0%' has unsaved changes. Leaving the page will discard these. Do you want to proceed?",
                                    [Model.getTypeFromOid(this.entity.oid).getDisplayValue(this.entity)]),
                                okCallback: lang.hitch(this, function(dlg) {
                                    topic.publish('navigate', route.name, route.pathParams);
                                })
                            }).show();
                        }
                        else {
                            topic.publish('navigate', route.name, route.pathParams);
                        }
                    }
                })
            });
            this.addChild(testBtn);
        },

        /**
         * Get the link navigation parameters
         * @returns Object with attributes name, pathParams
         */
        getLinkRoute: function() {
            var val = this.get("value");
            if (val) {
                var oid = val.replace(/^link:\/\//, '');
                var type = Model.getSimpleTypeName(Model.getTypeNameFromOid(oid));
                var id = Model.getIdFromOid(oid);
                var pathParams = { type:type, id:id };
                return {
                    name: "entity",
                    pathParams: pathParams
                };
            }
            return null;
        }
    });
});