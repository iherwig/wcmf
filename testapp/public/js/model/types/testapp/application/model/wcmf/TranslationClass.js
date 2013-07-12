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
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/wcmf/TranslationClass.js/Define) ENABLED START
// PROTECTED REGION END
], function(
    declare,
    Node
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/wcmf/TranslationClass.js/Params) ENABLED START
// PROTECTED REGION END
) {
    var Translation = declare([Node
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/wcmf/TranslationClass.js/Declare) ENABLED START
// PROTECTED REGION END
    ], {
        typeName: 'testapp.application.model.wcmf.Translation',
        isSortable: false,
        displayValues: [
            "objectid"
,             "attribute"
,             "language"
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
            name: "objectid",
            type: "String",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "attribute",
            type: "String",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "translation",
            type: "String",
            isEditable: false,
            inputType: 'textarea',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }, {
            name: "language",
            type: "String",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            regexp: '',
            regexpDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            isReference: false
        }],

        relations: []

// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/wcmf/TranslationClass.js/Body) ENABLED START
        , listView: 'js/ui/data/widget/EntityListWidget'
        , detailView: 'js/ui/data/widget/EntityFormWidget'
// PROTECTED REGION END
    });
// PROTECTED REGION ID(testapp/public/js/model/types/testapp/application/model/wcmf/TranslationClass.js/Static) ENABLED START
// PROTECTED REGION END
    return Translation;
});
  