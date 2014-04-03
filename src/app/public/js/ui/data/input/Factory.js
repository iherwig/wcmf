define( [
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/_base/array",
    "dojo/Deferred",
    "dojo/when",
    "../../../model/meta/Model",
    "../../../persistence/ListStore"
],
function(
    declare,
    lang,
    array,
    Deferred,
    when,
    Model,
    ListStore
) {
    var Factory = declare(null, {
    });

    /**
     * Load the control classes for a given entity type.
     * @param type The entity type name
     * @returns Deferred which returns a map with attribute names as
     * keys and control classes as values
     */
    Factory.loadControlClasses = function(type) {
        var deferred = new Deferred();

        var inputTypeMap = {};
        var typeClass = Model.getType(type);
        var attributes = typeClass.getAttributes('DATATYPE_ATTRIBUTE');

        // collect all control classes
        for (var i=0, count=attributes.length; i<count; i++) {
            var inputType = attributes[i].inputType;
            var controlClass = Factory.getControlClass(inputType);
            inputTypeMap[inputType] = controlClass;
        }

        var controls = [];
        for (var key in inputTypeMap) {
            var controlClass = inputTypeMap[key];
            if (array.indexOf(controls, inputTypeMap[key]) === -1) {
                controls.push(controlClass);
            }
        }

        require(controls, function() {
            // store loaded classes in inputTyp -> control map
            var result = {};
            for (var key in inputTypeMap) {
                var control = arguments[array.indexOf(controls, inputTypeMap[key])];
                if (!(control instanceof Function)) {
                    deferred.reject({ message: "Control for input type '"+key+"' not found."});
                }
                result[key] = control;
            }

            deferred.resolve(result);
        }, function(error) {
            deferred.reject(error);
        });
        return deferred;
    };

    Factory.getControlClass = function(inputType) {
        var inputTypes = appConfig.inputTypes;

        // get best matching control
        var bestMatch = '';
        for (var controlDef in inputTypes) {
            if (inputType.indexOf(controlDef) === 0 && controlDef.length > bestMatch.length) {
                bestMatch = controlDef;
            }
        }
        // get the control
        if (bestMatch.length > 0) {
            var controlClass = inputTypes[bestMatch];
            return controlClass;
        }
        // default
        return "app/js/ui/data/input/widget/TextBox";
    };

    /**
     * Called by list controls to retrive the value store
     * @param inputType The input type (contains the list definition after '#' char)
     * @returns Store
     */
    Factory.getListStore = function(inputType) {
        var listDef = Factory.getListDefinition(inputType);
        if (!listDef) {
            throw "Input type '"+inputType+"' does not contain a list definition";
        }
        return ListStore.getStore(listDef, appConfig.defaultLanguage);
    };

    /**
     * Translate the given value according to the list definition that
     * might be contained in the input type
     * @param inputType The input type (contains the list definition after '#' char)
     * @param value The value
     * @returns Deferred
     */
    Factory.translateValue = function(inputType, value) {
        var deferred = new Deferred();
        var listDef = Factory.getListDefinition(inputType);
        if (listDef) {
            var store = ListStore.getStore(listDef, appConfig.defaultLanguage);
            when(store.query(), lang.hitch(value, function(list) {
                for (var i=0, c=list.length; i<c; i++) {
                    var item = list[i];
                    // intentionally ==
                    if (store.getIdentity(item) == this) {
                        deferred.resolve(item.displayText);
                    }
                }
                deferred.resolve(this);
            }));
        }
        else {
            deferred.resolve(value);
        }
        return deferred;
    };

    /**
     * Get the list definition from the given input type
     * @param inputType The input type
     * @returns String or null, if no list input type
     */
    Factory.getListDefinition = function(inputType) {
        var parts = inputType.split("#");
        return parts.length === 2 ? parts[1] : null;
    };

    return Factory;
});