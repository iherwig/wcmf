dojo.provide("com.ibm.developerworks.EasyRestService");

(function() {
  var pa = com.ibm.developerworks.EasyRestService = function (path, serviceImpl, schema) {
    // Enforce the dojox.rpc.Rest trailing slash functionality
    path = path.match(/\/$/) ? path : (path + '/');
    
    // A dojox.rpc.Service implementation is a function with 3 function members
    var service;
    // GET function
    service = function(id, args) {
      return _execXhr("get", id, args);
    };
    // POST function member
    service['post'] = function(id, value) {
      return _execXhr("post", id, value);
    };
    // PUT function member
    service['put'] = function(id, value) {
      return _execXhr("put", id, value);
    };
    // DELETE function member
    service['delete'] = function(id) {
      return _execXhr("delete", id);
    };
    
    // Generic XHR function for all methods
    var _execXhr = function(method, id, content) {
      // Transform the method string
      var methodCapitalised = method.substring(0,1).toUpperCase() 
        + method.substring(1).toLowerCase();
      var methodUpperCase = method.toUpperCase();
      var methodLowerCase = method.toLowerCase();
      
      // Get the transformer functions
      var argumentsTransformer = service["transform" + methodCapitalised + "Arguments"];
      var resultTransformer = service["transform" + methodCapitalised + "Results"];
      
      // Construct the standard query
      var serviceArgs = {
        url : path + (dojo.isObject(id) ? '?' + dojo.objectToQuery(id) : 
          (id == null ? "" : id)), 
        handleAs : "json",
        contentType : "application/json",
        sync : false,
        headers : { Accept : "application/json,application/javascript" }
      };
      
      // Transform the arguments
      // NOTE: argumentsTransformer has a reference to "service"
      serviceArgs = argumentsTransformer(serviceArgs, arguments);

      // Copy the content into the appropriate *Data arg
      // getData, putData, postData, deleteData
      // NOTE: If you want your arguments transformer to edit the *Data arg directly, 
      // move the arguments transformer invocation to after this call 
      serviceArgs[methodLowerCase + 'Data'] = content;
            
      // Kick off the call
      var xhrFunction = dojo['xhr' + methodCapitalised];
      var deferred = xhrFunction(serviceArgs);
      // Add our result transformer
      // NOTE: resultTransformer has a reference to "service" too
      deferred.addCallback(dojo.partial(resultTransformer, deferred));
      
      return deferred;
    };

    // Mix in the service hooks
    // Uses a "default" implementation that does nothing
    // Service hooks will have a reference to the "service" object in their context
    dojo.mixin(service, 
      new com.ibm.developerworks.EasyRestService.DefaultHooks(), 
      serviceImpl);
    
    // Now remove any default _constructor() methods
    // This is necessary as the JsonRestStore stack uses _constructor() differently
    delete service['_constructor'];
    // Remove the declaredClass member if it has been added
    delete service['declaredClass'];
    
    // Save the path away
    service.servicePath = path;
    // Save the schema
    service._schema = schema;
    
    return service;
  };
})();

dojo.declare("com.ibm.developerworks.EasyRestService.DefaultHooks", null, {
  transformGetArguments: function(serviceArgs) {
    // Alter serviceArgs to provide the information the backend
    // service requires
    return serviceArgs;
  },
  transformPutArguments: function(serviceArgs) {
    // Alter serviceArgs to provide the information the backend
    // service requires
    return serviceArgs;
  },
  transformPostArguments: function(serviceArgs) {
    // Alter serviceArgs to provide the information the backend
    // service requires
    return serviceArgs;
  },
  transformDeleteArguments: function(serviceArgs) {
    // Alter serviceArgs to provide the information the backend
    // service requires
    return serviceArgs;
  },
  transformGetResults: function(deferred, results) {
    /*
     * JsonRestStore expects the following format:
     * [
     *  { id: "1", ... },
     *  { id: "2", ... },
     *  ...
     * ] 
     */
    return results;
  },
  transformPutResults: function(deferred, results) {
    /*
     * JsonRestStore does not expect any specific content here
     */
    return results;
  },
  transformPostResults: function(deferred, results) {
    /*
     * JsonRestStore expects:
     * 1) A "Location" response header with location of the new item.
     *      From the Dojo API:
     *          The server’s response should include a Location header
     *          that indicates the id of the newly created object.
     *          This id will be used for subsequent PUT and DELETE 
     *          requests. JsonRestStore also includes a 
     *          Content-Location header that indicates the temporary
     *          randomly generated id used by client, and this 
     *          location is used for subsequent PUT/DELETEs if no 
     *          Location header is provided by the server or if 
     *          a modification is sent prior to receiving a response 
     *          from the server.
     *    NB: There is no JS method for altering response headers.  
     *      You may wish to try overriding the 
     *      deferred.ioArgs.xhr.getResponseHeader() method with your
     *      own implementation.
     * 2) The new item in the following format:
     * { id: "1", ... }
     */
    return results;
  },
  transformDeleteResults: function(deferred, results) {
    /*
     * JsonRestStore does not expect any specific content here
     */
    return results;
  }
});