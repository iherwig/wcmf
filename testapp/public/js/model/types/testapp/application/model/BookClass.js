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
 * This file was generated by ChronosGenerator  from cwm-export.uml on Fri Jul 12 19:09:21 CEST 2013.
 * Manual modifications should be placed inside the protected regions.
 */
define([
    "dojo/_base/declare",
    "app/model/meta/Node"
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/BookClass.js/Define) ENABLED START
// PROTECTED REGION END
], function(
    declare,
    Node
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/BookClass.js/Params) ENABLED START
// PROTECTED REGION END
) {
    var Book = declare([Node
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/BookClass.js/Declare) ENABLED START
// PROTECTED REGION END
    ], {
        typeName: 'testapp.application.model.Book',
        isSortable: false,
        displayValues: [
            "title"
        ],
        pkNames: [
            "id"
        ],

        attributes: [{
            name: "id",
            type: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_IGNORE'],
            isReference: false
        }, {
            name: "fk_publisher_id",
            type: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_IGNORE'],
            isReference: false
        }, {
            name: "title",
            type: "String",
            isEditable: true,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "description",
            type: "String",
            isEditable: true,
            inputType: 'textarea',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "year",
            type: "Date",
            isEditable: true,
            inputType: 'date',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "created",
            type: "Date",
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
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }],

        relations: [{
            name: "Chapter",
            type: "Chapter",
            aggregrationKind: "shared",
            maxMultiplicity: "unbounded",
            thisEndName: "Book",
            relationType: "child"
        }, {
            name: "Publisher",
            type: "Publisher",
            aggregrationKind: "none",
            maxMultiplicity: "1",
            thisEndName: "Book",
            relationType: "parent"
        }]

// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/BookClass.js/Body) ENABLED START
        , listView: 'js/ui/data/widget/EntityListWidget'
        , detailView: 'js/ui/data/widget/EntityFormWidget'
// PROTECTED REGION END
    });
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/BookClass.js/Static) ENABLED START
// PROTECTED REGION END
    return Book;
});
  