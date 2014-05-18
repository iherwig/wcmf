define([
    "dojo/_base/declare",
    "dojo/_base/json",
    "../locale/Dictionary"
], function(
    declare,
    json,
    Dict
) {
    /**
     * Class for handling backend errors.
     */
    var BackendError = declare(null, {
    });

    /**
     * Parse error data
     * @param errorData obj passed to xhr error handler
     * @return Obj with attributes message, code, data
     */
    BackendError.parseResponse = function(errorData) {
        var response = errorData.response;

        // make sure that response data is converted to json
        if (response && typeof(response.data) === "string") {
            response.data = json.fromJson(response.data || null);
        }

        // check for most specific attributes

        // get message
        var message = Dict.translate("Backend error");
        if (response && response.data && response.data.errorMessage) {
            message = response.data.errorMessage;
        }
        else if (errorData.errorMessage) {
            message = errorData.errorMessage;
        }
        else if (errorData.message) {
            message = errorData.message;
        }

        // get code
        var code = "";
        if (response && response.data && response.data.errorCode) {
            code = response.data.errorCode;
        }
        else if (errorData.errorCode) {
            code = errorData.errorCode;
        }
        else if (errorData.code) {
            code = errorData.code;
        }

        // get optional data
        var data = {};
        if (response && response.data && response.data.errorData) {
            data = response.data.errorData;
        }
        else if (errorData.errorData) {
            code = errorData.errorData;
        }
        else if (errorData.data) {
            data = errorData.data;
        }

        return {
            message: message,
            code: code,
            data: data
        };
    };

    return BackendError;
});
