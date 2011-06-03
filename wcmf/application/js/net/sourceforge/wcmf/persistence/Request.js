dojo.provide("wcmf.persistence.Request");

/**
 * @class Request This class encapsulates functionality for sending data to the
 *        server
 */
dojo.declare("wcmf.persistence.Request", null, {

  serverUrl: wcmf.appURL,
  defaultParameters: {
    sid: wcmf.sid,
    controller: wcmf.controller,
    context: wcmf.context,
    action: wcmf.action,
    responseFormat: wcmf.responseFormat
  },
  postSeqVal: 0,

  /**
   * Sends the request using Ajax.
   *
   * @param parameters
   *            An object defining the parameters to be send to the
   *            server. May be used to overwrite the default request
   *            parameters
   * @param elementId
   *            The id of the DOM element that contains input fields
   *            whose values should be send to the server [maybe null]
   * @return dojo.Deferred promise
   */
  sendAjax: function(parameters, elementId) {
    return this.sendInternal(dojo.xhrPost, parameters, elementId);
  },

  /**
   * Sends the request using an IFrame.
   *
   * @param parameters
   *            An object defining the parameters to be send to the
   *            server. May be used to overwrite the default request
   *            parameters
   * @param elementId
   *            The id of the DOM element that contains input fields
   *            whose values should be send to the server [maybe null]
   * @return dojo.Deferred promise
   */
  sendIFrame: function(parameters, elementId) {
    return this.sendInternal(dojo.io.iframe.send, parameters, elementId);
  },

  /**
   * Internal send function
   */
  sendInternal: function(serverCallFunction, parameters, elementId) {

    parameters = parameters || {};

    // copy all form fields to the parameters
    if (elementId) {
      var widgets = dijit.findWidgets(dojo.byId(elementId));
      dojo.forEach(widgets, function(input) {
        if (parameters[input.name] == undefined) {
          parameters[input.name] = input.get('value');
        }
      });
    }
    var mergedParameters = dojo.mixin(this.defaultParameters, parameters);

    // set the expected response format
    var handleAs = mergedParameters.responseFormat;
    if (handleAs.toLowerCase() == 'soap') {
      handleAs = 'xml';
    }
    else if (handleAs.toLowerCase() == 'html') {
      handleAs = 'text';
    }

    // set up the server call
    var promise = new dojo.Deferred();
    var deferred = serverCallFunction({
      url: this.serverUrl,
      preventCache: true,
      content: mergedParameters,
      handleAs: handleAs
    });

    // set callbacks
    if (deferred) {
      deferred.addCallback(function(response) {
        // check for errors in json response
        if (handleAs == 'json' && !response.success) {
          var errorMessage = response.errorMessage || wcmf.Message.get('Server error.');
          wcmf.Error.show(errorMessage);
          promise.errback(errorMessage);
        }
        else {
          // call other callbacks
          if (promise.fired !== 1) {
            promise.callback(response);
          }
        }
      });
      deferred.addErrback(function(response){
        var errorMessage = wcmf.Message.get('Server error.');
        wcmf.Error.show(errorMessage);
        promise.errback(errorMessage);
      });
    };
    // return the deferred for other callbacks
    return promise;
  }
});