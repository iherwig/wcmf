define( [
    "dojo/_base/declare",
    "dojo/_base/array",
    "dojo/_base/kernel",
    "dojo/Deferred",
    "../../../model/meta/Model",
    "../../../model/meta/_InputTypeList"
],
function(
    declare,
    array,
    kernel,
    Deferred,
    Model,
    InputTypeDefinitions
) {
    var Factory = declare(null, {
    });

    /**
     * Registry for cached list definitions
     */
    kernel.global.listDefinitions = {};

    /**
     * Load the control classes for a given entity type.
     * @param type The entity type name
     * @returns Deferred which returns an map with attribute names as
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
        // get best matching control
        var bestMatch = '';
        for (var controlDef in InputTypeDefinitions) {
            if (inputType.indexOf(controlDef) === 0 && controlDef.length > bestMatch.length) {
                bestMatch = controlDef;
            }
        }
        // get the control
        if (bestMatch.length > 0) {
          var controlClass = InputTypeDefinitions[bestMatch];
          return controlClass;
        }
        // default
        return "js/ui/data/input/widget/TextBox";
    };

    /**
     * Called by list controls to retrive the list of values
     * @param inputType The input type (contains the list definition after '#' char)
     * @returns Array with keys, values
     */
    Factory.getListValues = function(inputType) {
        var deferred = new Deferred();
        // TODO: get list from server and cache if allowed by server
        return deferred;
    };

    return Factory;
});