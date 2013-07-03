define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "../data/EntityPage",
    "../../locale/Dictionary",
    "dojo/text!./template/PrincipalPage.html"
], function (
    require,
    declare,
    lang,
    EntityPage,
    Dict,
    template
) {
    return declare([EntityPage], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('User Management'),

        baseRoute: "principal",
        types: ["UserRDB", "RoleRDB"]
    });
});