
// dynamically define requirements
var requirements = [
    "dojo/_base/declare"
];

// add display type renderers to requirements
var displayTypes = appConfig.displayTypes;
for (var key in displayTypes) {
    requirements.push(displayTypes[key]);
}

define(
    requirements
,
function(
) {
    // extract requirements manually from arguments object
    var declare = arguments[0];
    var Renderer = declare(null, {
    });

    /**
     * Render the given value according to the given attribute definition.
     * @param value The value
     * @param attribute The attribute definition
     * @param synch Boolean, if true, return a string immediatly
     * @returns Deferred/String
     */
    Renderer.render = function(value, attribute, synch) {
        var renderer = Renderer.getRenderer(attribute.displayType);
        if (renderer instanceof Function) {
            return renderer(value, attribute, synch);
        }
        return value;
    };

    Renderer.getRenderer = function(displayType) {
        if (displayType) {
            var displayTypes = Renderer.renderers;

            // get best matching renderer
            var bestMatch = '';
            for (var rendererDef in displayTypes) {
                if (displayType.indexOf(rendererDef) === 0 && rendererDef.length > bestMatch.length) {
                    bestMatch = rendererDef;
                }
            }
            // get the renderer
            if (bestMatch.length > 0) {
                var renderer = displayTypes[bestMatch];
                return renderer;
            }
        }
        // default
        return "app/js/ui/data/display/renderer/Text";
    };

    // initialize renderers
    Renderer.renderers = {};
    var i=0;
    for (var key in appConfig.displayTypes) {
        Renderer.renderers[key] = arguments[++i];
    }

    return Renderer;
});