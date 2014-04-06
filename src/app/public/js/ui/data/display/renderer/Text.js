define([
    "../../input/Factory"
],
function(
    ControlFactory
) {
    return function(value, attribute, synch) {
        return synch ? value : ControlFactory.translateValue(attribute.inputType, value);
    };
});