define( [
    "dojo/_base/declare",
    "dojo/_base/array",
    "dojo/Deferred",
    "../../../model/meta/Model"
],
function(
    declare,
    array,
    Deferred,
    Model
) {
    var Factory = declare(null, {
    });

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
        if (inputType === 'text') {
            return "js/ui/data/input/widget/TextBox";
        }
        else if (inputType === 'textarea') {
            return "js/ui/data/input/widget/TextArea";
        }
        else if (inputType === 'date') {
            return "js/ui/data/input/widget/Date";
        }

        return "js/ui/data/input/widget/TextBox";
    };

    return Factory;
});