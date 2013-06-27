define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "./nls/"+appConfig.uiLanguage
],
function(
    declare,
    lang,
    dict
) {
    var Dictionary = declare(null, {
    });

    /**
     * Translate templates to the ui language.
     * It will translate text_to_translate in occurences of {translate:text_to_translate}
     * in the given template. Usage:
     *
     * lang.replace(template, Dict.tplReplace)
     *
     * @param _ To be ignored
     * @param text Text to be translated
     * @returns String
     */
    Dictionary.tplTranslate = function(_, text) {
        var key = text.replace(/^translate:/, "");
        // dict maybe "not-a-module", if language file does not exist
        return (typeof dict === "string" | !dict[key]) ? key : dict[key];
    };

    /**
     * Translate the given text into the ui language. Use params array
     * to replace {0}, {1}, .... variables in the text.
     *
     * @param text Text to be translated
     * @param params Array of replacements [optional]
     * @returns String
     */
    Dictionary.translate = function(text, params) {
        // dict maybe "not-a-module", if language file does not exist
        var translation = (typeof dict === "string" | !dict[text]) ? text : dict[text];
        // replace parameters
        if (typeof params === "object") {
            return lang.replace(translation, params);
        }
        else {
            return translation;
        }
    };

    return Dictionary;
});