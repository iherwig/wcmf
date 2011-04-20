dojo.provide("wcmf.persistence.DionysosService");

/**
 * @class DionysosService This class is used to exchange objects and
 * their modifications with the server. There is one service for each type.
 * DionysosService implements the Dionysos protocol. See:
 * http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt
 */
dojo.declare("wcmf.persistence.DionysosService", null, {

  /**
   * The type of object that is handled
   */
  modelClass: null,

  /**
   * The service function used as service paramter for dojox.data.ServiceStore
   */
  service: null,

  /**
   * Constructor
   * @param modelClass The model class whose instances this service handles
   */
  constructor: function(modelClass) {
    this.modelClass = modelClass;

    // define the transformers
    // TODO: this can be done in an extra class like the base class does it
    var self = this;
    var transformers = {
      transformGetArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.name+":/", "");
        if (args.content) {
          if (args.content.count) {
            args.content.limit = args.content.count;
          }
          if (args.content.start) {
            args.content.offset = args.content.start;
          }
          if (args.content.sort) {
            var sortDef = args.content.sort[0];
            args.content.sortFieldName = sortDef.attribute;
            args.content.sortDirection = sortDef.descending ? "desc" : "asc";
          }
          // remove the query object. the content will be serialized
          // by dojo as key/value pairs automatically
          if (args.content.query) {
            delete args.content.query;
          }
        }
        return args;
      },
      transformPutArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.name+":/", "");
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
        args.url = args.url.replace("/"+self.modelClass.name+":/", "");
        if (args.postData) {
          var postData = {};
          var postDataTmp = dojo.fromJson(args.postData);
          postData.attributes = postDataTmp;
          postData.controller = 'TerminateController';
          args.postData = dojo.toJson(postData);
        }
        return args;
      },
      transformDeleteArguments: function(args) {
        args.url = args.url.replace("/"+self.modelClass.name+":/", "");
        return args;
      },
      transformGetResults: function(deferred, results) {
        if (!self.handleError(deferred, results)) {
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
        }
      },
      transformPutResults: function(deferred, results) {
        if (!self.handleError(deferred, results)) {
          // results is server's update response
          result = [];
          var obj = results.attributes;
          // add the oid field
          obj['oid'] = results.oid;
          result = obj;
          return result;
        }
      },
      transformPostResults: function(deferred, results) {
        if (!self.handleError(deferred, results)) {
          // results is server's create response
          result = [];
          var obj = results.attributes;
          // add the oid field
          obj['oid'] = results.oid;
          result = obj;
          return result;
        }
      },
      transformDeleteResults: function(deferred, results) {
        if (!self.handleError(deferred, results)) {
          return results;
        }
      }
    };

    // create the service function
    this.service = com.ibm.developerworks.EasyRestService(this.getServiceUrl(), transformers);
  },

  /**
   * Check for errors in the result and call the deferred's errback
   * method if true
   * @return Boolean True/False wether there is an error or not
   */
  handleError: function(deferred, results) {
    if (results && results.errorCode) {
      deferred.errback(new Error(results.errorMessage));
      return true;
    }
    return false;
  },

  /**
   * Return the get service function with methods put/post/delete
   * @see dojox.data.ServiceStore
   * @return Function
   */
  getServiceFunction: function() {
    return this.service;
  },

  /**
   * Get the url for this service
   * @return String
   */
  getServiceUrl: function() {
    return 'rest/'+this.modelClass.name+'/';
  },

  /**
   * Get the url for a given object id
   * @param oid The object id
   * @return String
   */
  getObjectUrl: function(oid) {
    return '?action=detail&oid='+oid;
  }
});
