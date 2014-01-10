define( [
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../../../model/meta/Model",
    "../../data/widget/EntityFormWidget",
    "../../../locale/Dictionary",
    "dojo/text!./template/PrincipalFormWidget.html"
],
function(
    require,
    declare,
    lang,
    Model,
    EntityFormWidget,
    Dict,
    template
) {
    return declare([EntityFormWidget], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,

        baseRoute: "principal",

        /**
         * Get the type's relations to display in the widget
         * @returns Array
         */
        getRelations: function() {
            var typeClass = Model.getType(this.type);
            return [typeClass.getRelation(Model.getSimpleTypeName(appConfig.roleType))];
        }
    });
});