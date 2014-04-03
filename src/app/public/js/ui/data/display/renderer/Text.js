define([
    "dojo/dom",
    "../../input/Factory",
    "dojox/uuid/generateRandomUuid",
    "dojo/domReady!"
],
function(
    dom,
    ControlFactory,
    uuid
) {
    return function(value, attribute) {
        var id = 't-'+uuid();
        ControlFactory.translateValue(attribute.inputType, value).then(function(value) {
            dom.byId(id).innerHTML = value;
        });
        return '<span id='+id+'><i class="fa fa-spinner fa-spin"></i></span>';
    };
});