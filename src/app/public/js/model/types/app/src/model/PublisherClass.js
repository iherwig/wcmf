/*
 * Copyright (c) 2013 The Olympos Development Team.
 * 
 * http://sourceforge.net/projects/olympos/
 * 
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html. If redistributing this code,
 * this entire header must remain intact.
 */

/**
 * This file was generated by ChronosGenerator  from cwm-export.uml.
 * Manual modifications should be placed inside the protected regions.
 */
define([
    "dojo/_base/declare",
    "app/js/model/meta/Node"
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/PublisherClass.js/Define) ENABLED START
// PROTECTED REGION END
], function(
    declare,
    Node
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/PublisherClass.js/Params) ENABLED START
// PROTECTED REGION END
) {
    var Publisher = declare([Node
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/PublisherClass.js/Declare) ENABLED START
// PROTECTED REGION END
    ], {
        typeName: "app.src.model.Publisher",
        description: "?A publisher publishes books.",
        isSortable: false,
        displayValues: [
            "name"
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
            name: "name",
            type: "String",
            description: "?",
            isEditable: true,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "created",
            type: "Date",
            description: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "creator",
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
            name: "modified",
            type: "Date",
            description: "?",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "last_editor",
            type: "String",
            description: "?",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }],

        relations: [{
            name: "Book",
            type: "Book",
            aggregationKind: "composite",
            maxMultiplicity: "unbounded",
            thisEndName: "Publisher",
            relationType: "child"
        }, {
            name: "Author",
            type: "Author",
            aggregationKind: "none",
            maxMultiplicity: "unbounded",
            thisEndName: "Publisher",
            relationType: "child"
        }]

// PROTECTED REGION ID(app/public/js/model/types/app/src/model/PublisherClass.js/Body) ENABLED START
        , listView: 'app/js/ui/data/widget/EntityListWidget'
        , detailView: 'app/js/ui/data/widget/EntityFormWidget'
// PROTECTED REGION END
    });
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/PublisherClass.js/Static) ENABLED START
// PROTECTED REGION END
    return Publisher;
});
  