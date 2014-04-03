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
 * This file was generated by ChronosGenerator  from model.uml.
 * Manual modifications should be placed inside the protected regions.
 */
define([
    "dojo/_base/declare",
    "app/js/model/meta/Node"
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/wcmf/UserConfigClass.js/Define) ENABLED START
// PROTECTED REGION END
], function(
    declare,
    Node
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/wcmf/UserConfigClass.js/Params) ENABLED START
// PROTECTED REGION END
) {
    var UserConfig = declare([Node
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/wcmf/UserConfigClass.js/Declare) ENABLED START
// PROTECTED REGION END
    ], {
        typeName: "app.src.model.wcmf.UserConfig",
        description: "",
        isSortable: false,
        displayValues: [
            "key"
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
            validateType: '',
            validateDesc: '',
            tags: ['DATATYPE_IGNORE'],
            defaultValue: null,
            isReference: false
        }, {
            name: "fk_user_id",
            type: "",
            description: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            validateType: '',
            validateDesc: '',
            tags: ['DATATYPE_IGNORE'],
            defaultValue: null,
            isReference: false
        }, {
            name: "key",
            type: "String",
            description: "",
            isEditable: false,
            inputType: 'text',
            displayType: 'text',
            validateType: '',
            validateDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            defaultValue: null,
            isReference: false
        }, {
            name: "val",
            type: "String",
            description: "",
            isEditable: true,
            inputType: 'text',
            displayType: 'text',
            validateType: '',
            validateDesc: '',
            tags: ['DATATYPE_ATTRIBUTE'],
            defaultValue: null,
            isReference: false
        }],

        relations: [{
            name: "User",
            type: "User",
            aggregationKind: "none",
            maxMultiplicity: "1",
            thisEndName: "UserConfig",
            relationType: "parent"
        }]

// PROTECTED REGION ID(app/public/js/model/types/app/src/model/wcmf/UserConfigClass.js/Body) ENABLED START
        , listView: 'app/js/ui/data/widget/EntityListWidget'
        , detailView: 'app/js/ui/data/widget/EntityFormWidget'
// PROTECTED REGION END
    });
// PROTECTED REGION ID(app/public/js/model/types/app/src/model/wcmf/UserConfigClass.js/Static) ENABLED START
// PROTECTED REGION END
    return UserConfig;
});
  