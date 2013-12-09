define([
    "dojo/_base/declare",
    "../../model/meta/Node"
], function(
    declare,
    Node
) {
    var SearchResult = declare([Node
    ], {
        typeName: "SearchResult",
        description: "?",
        isSortable: false,
        displayValues: [
            "summary", "type"
        ],
        pkNames: [
            "id"
        ],

        attributes: [{
            name: "id",
            type: "",
            description: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_IGNORE'],
            isReference: false
        }, {
            name: "summary",
            type: "String",
            description: "?",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "type",
            type: "String",
            description: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }],

        relations: []

        , listView: '../data/widget/EntityListWidget'
        , detailView: '../data/widget/EntityFormWidget'
    });
    return SearchResult;
});
