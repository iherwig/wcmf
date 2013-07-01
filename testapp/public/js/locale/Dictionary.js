define([
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/request"
],
function(
    declare,
    lang,
    request
) {
    var Dictionary = declare(null, {
    });

    /**
     * Translate templates to the ui language.
     * It will translate text_to_translate in occurences of {translate:text_to_translate}
     * or {translate:text_%0%_%1%|r0,r1} in the given template. Usage:
     *
     * lang.replace(template, Dict.tplReplace)
     *
     * @param _ To be ignored
     * @param text Text to be translated
     * @returns String
     */
    Dictionary.tplTranslate = function(_, text) {
        var dict = Dictionary.getDictionary();
        if (text.match(/^translate:/)) {
            var key = text.replace(/^translate:/, "");
            // check for message|params combination
            var params = [];
            if (key.indexOf("|") >= 0) {
                var splitKey = key.split("|");
                key = splitKey[0];
                params = splitKey[1].split(",");
            }
            // dict maybe "not-found", if language file does not exist
            var translation = (typeof dict === "string" | !dict[key]) ? key : dict[key];
            // replace parameters
            if (typeof params === "object") {
                return lang.replace(translation, params, /\%([^\%]+)\%/g);
            }
            else {
                return translation;
            }
        }
        else {
            return _;
        }
    };

    /**
     * Translate the given text into the ui language. Use params array
     * to replace %0%, %1%, .... variables in the text.
     *
     * @param text Text to be translated
     * @param params Array of replacements [optional]
     * @returns String
     */
    Dictionary.translate = function(text, params) {
        var dict = Dictionary.getDictionary();
        // dict maybe "not-found", if language file does not exist
        var translation = (typeof dict === "string" | !dict[text]) ? text : dict[text];
        // replace parameters
        if (typeof params === "object") {
            return lang.replace(translation, params, /\%([^\%]+)\%/g);
        }
        else {
            return translation;
        }
    };

    Dictionary.dict = null;

    Dictionary.getDictionary = function() {
        if (Dictionary.dict === null) {
            // load dictionary on first call
            request.post("main.php", {
                sync: true,
                timeout: 100,
                data: {
                    action: "messages",
                    language: appConfig.uiLanguage
                },
                headers: {
                    "Accept" : "application/json"
                },
                handleAs: 'json'

            }).then(function(response) {
                // callback completes
                Dictionary.dict = response;
            }, function(error) {
                // error
                Dictionary.dict = "not-found";
            });
            // wait until resolved
            // TODO is there a better way to do that?
            for (var i=0; i<10000; i++) {
              if (Dictionary.dict !== null) {
                  break;
              }
            };
            return Dictionary.dict;
        }
        else {
            // already loaded
            return Dictionary.dict;
        }
    };

    return Dictionary;
});