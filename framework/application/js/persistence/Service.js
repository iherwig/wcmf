/**
 * @class Service This class is used to exchange objects and their modifications
 * with the server. There is one service for each type.
 */
dojo.provide("wcmf.persistence.Service");

(function() {
  /**
   * Constructor
   * @param modelClass The model class this service handles 
   */
  var pa = wcmf.persistence.Service = function (modelClass) {
	
	this.modelClass = modelClass;
	var self = this;

	// define the transformers (modelClass is available as self.modelClass)
    var transformers = { 
      transformGetArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        return args; 
      },
      transformPutArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        return args; 
      },
      transformPostArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        return args; 
      },
      transformDeleteArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        return args; 
      },
      transformGetResults: function(deferred, results) {
        var objList = results.list;
        var result = [];
        for (var i=0, count=objList.length; i<count; i++) {
          var obj = objList[i].attributes;
          // add the oid field
          obj['oid'] = objList[i].oid;
          result.push(obj);
        }
        return result;
      }
    };
    // create the service
    var service = new com.ibm.developerworks.EasyRestService(
      "rest/"+this.modelClass.type+"/", transformers);
    
    return service;
  };
})();
