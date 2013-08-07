define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../data/EntityListPage",
    "../../model/meta/Model",
    "../../locale/Dictionary",
    "dojo/text!./template/PrincipalListPage.html"
], function (
    require,
    declare,
    lang,
    EntityListPage,
    Model,
    Dict,
    template
) {
    return declare([EntityListPage], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('User Management'),

        baseRoute: "principal",
        types: [
          Model.getSimpleTypeName(appConfig.userType),
          Model.getSimpleTypeName(appConfig.roleType)
        ]
    });
});