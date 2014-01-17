define([
    "require",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/topic",
    "../_include/_PageMixin",
    "../_include/_NotificationMixin",
    "../_include/widget/NavigationWidget",
    "../_include/widget/GridWidget",
    "../../model/meta/Model",
    "../../persistence/SearchStore",
    "./SearchResult",
    "../../action/Edit",
    "../../locale/Dictionary",
    "dojo/text!./template/SearchResultPage.html"
], function (
    require,
    declare,
    lang,
    topic,
    _Page,
    _Notification,
    NavigationWidget,
    GridWidget,
    Model,
    SearchStore,
    SearchResult,
    Edit,
    Dict,
    template
) {
    return declare([_Page, _Notification], {

        templateString: lang.replace(template, Dict.tplTranslate),
        contextRequire: require,
        title: Dict.translate('Searchresult'),

        constructor: function(params) {
            this.searchterm = this.request.getQueryParam("q");

            // register search result type if not done already
            if (!Model.isKnownType("SearchResult")) {
              Model.registerType(new SearchResult());
            }
        },

        postCreate: function() {
            this.inherited(arguments);
            this.setTitle(this.title+" - "+this.searchterm);

            // create widget
            this.buildForm();

            this.own(
                topic.subscribe('ui/_include/widget/GridWidget/unknown-error', lang.hitch(this, function(error) {
                    this.showBackendError(error);
                }))
            );
        },

        buildForm: function() {
            new GridWidget({
                type: "SearchResult",
                store: SearchStore.getStore(this.searchterm),
                actions: this.getGridActions(),
                enabledFeatures: []
            }, this.gridNode);
        },

        getGridActions: function() {

            var editAction = new Edit({
                page: this,
                route: "entity"
            });

            return [editAction];
        }
    });
});