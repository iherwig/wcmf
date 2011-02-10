/**
 * @class DionysosService This class is used to exchange objects and 
 * their modifications with the server. There is one service for each type.
 * DionysosService implements the Dionysos protocol. See:
 * http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt
 */
dojo.provide("wcmf.persistence.Service");

(function() {
  /**
   * Constructor
   * @param modelClass The model class this service handles 
   */
  var pa = wcmf.persistence.DionysosService = function (modelClass) {
	
	this.modelClass = modelClass;
	var self = this;

	// define the transformers (modelClass is available as self.modelClass)
    var transformers = { 
      transformGetArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        if (args.content) {
          if (args.content.count) {
            args.content.limit = args.content.count;
          }
          if (args.content.start) {
            args.content.offset = args.content.start;
          }
        }
        return args; 
      },
      transformPutArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.type+":/", "");
        if (args.putData) {
          var putData = {};
          var putDataTmp = dojo.fromJson(args.putData);
          putData.oid = putDataTmp.oid;
          putData.lastChange = "";
          putData.overrideLock = false;
          putData.attributes = putDataTmp;
          putData.controller = 'TerminateController';
          delete putData.attributes.oid;
          args.putData = dojo.toJson(putData);
        }
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
        deferred.fullLength = results.totalCount;
        return result;
      },
      transformPutResults: function(deferred, results) {
        result = [];
        var obj = results.attributes;
        // add the oid field
        obj['oid'] = results.oid;
        result = obj;
        return result;
      },
      transformPostResults: function(deferred, results) {
        return results;
      },
      transformDeleteResults: function(deferred, results) {
        return results;
      }
    };
    // create the service
    var service = new com.ibm.developerworks.EasyRestService(
      "rest/"+this.modelClass.type+"/", transformers);
    
    return service;
  };
})();
