define(["dojo/_base/xhr", "dojo/_base/json", "dojo/io-query", "dojo/_base/declare", "dojo/_base/Deferred", "dojo/store/util/QueryResults"
], function(xhr, json, ioQuery, declare, Deferred, QueryResults) {

/**
 * @class DionysosServiceAdapter This class implements a ServiceAdapter
 * for the Dionysos protocol. See:
 * http://olympos.svn.sourceforge.net/viewvc/olympos/trunk/olympos/dionysos/docs/Dionysos%20Specification%20JSON.odt
 */
declare("wcmf.persistence.DionysosServiceAdapter", null, {

  /**
   * The type of object that is handled (wcmf.mode.meta.Node instance)
   */
  modelClass: null,

  /**
   * The language of the handled objects
   */
  language: null,

  /**
   * The service url
   */
  target: null,

  /**
   * Constructor
   * @param modelClass The model class whose instances this adapter handles
   * @param language The langauge of the handled objects
   */
  constructor: function(modelClass, language) {
    this.modelClass = modelClass;
    this.language = language;
    this.target = 'rest/'+this.language+"/"+this.modelClass.name+'/';
  },

  /**
   * Accept header to use on HTTP requests
   */
  accepts: "application/javascript, application/json",

  /**
   * @see dojo.store.api.Store.get
   */
  get: function(id) {
    // server call
    var deferred = xhr("GET", {
      url: this.target + id,
      handleAs: "json",
      headers: {
        Accept: this.accepts
      }
    });

    // result handler
    var self = this;
    deferred.addCallback(function(results) {
      var result = null;
      if (!self.handleError(deferred, results)) {
        if (results.object) {
          var obj = results.object.attributes;
          // add the oid field
          obj['oid'] = results.object.oid;
          result = obj;
        }
      }
      return result;
    });

    return deferred;
  },

  /**
   * @see dojo.store.api.Store.put
   * @see dojo.store.api.Store.add
   */
  addOrUpdate: function(object, directives) {
    directives = directives || {};
    var hasId = typeof directives.id != "undefined";
    var isNew = directives.overwrite == false;

    // server call
    var deferred = xhr(hasId && !isNew ? "PUT" : "POST", {
      url: hasId ? this.target + directives.id : this.target,
      postData: json.toJson({
        oid: isNew ? undefined : object.oid,
        lastChange: isNew ? undefined : "",
        overrideLock: isNew ? undefined : false,
        attributes: object,
        controller: 'TerminateController'
      }),
      handleAs: "json",
      headers:{
        "Content-Type": "application/json",
        Accept: this.accepts
      }
    });

    // result handler
    var self = this;
    deferred.addCallback(function(results) {
      var result = null;
      if (!self.handleError(deferred, results)) {
        // results is server's create/update response
        result = [];
        var obj = results.attributes;
        // add the oid field
        obj['oid'] = results.oid;
        result = obj;
      }
      return result;
    });

    return deferred;
  },

  /**
   * @see dojo.store.api.Store.remove
   */
  remove: function(id) {
    // server call
    var deferred = xhr("DELETE", {
      url: this.target + id
    });

    // result handler
    var self = this;
    deferred.addCallback(function(results) {
      var result = null;
      if (!self.handleError(deferred, results)) {
        result = results;
      }
      return result;
    });

    return deferred;
  },
  /**
   * @see dojo.store.api.Store.query
   */
  query: function(query, options) {
    options = options || {};

    var content = {};
    if (options.start >= 0) {
      content.offset = options.start;
    }
    if (options.count >= 0) {
      content.limit = options.count;
    }

    if (options.sort) {
      var sortDef = options.sort[0];
      if (sortDef) {
        content.sortFieldName = sortDef.attribute;
        content.sortDirection = sortDef.descending ? "desc" : "asc";
      }
    }

    var deferred = xhr("GET", {
      url: this.target + "?" + ioQuery.objectToQuery(content),
      handleAs: "json",
      headers: {
        Accept: this.accepts
      }
    });

    // result handler
    var self = this;
    deferred.addCallback(function(results) {
      var result = [];
      if (!self.handleError(deferred, results)) {
        var objList = results.list;
        if (objList) {
          // object list
          for (var i=0, count=objList.length; i<count; i++) {
            var obj = objList[i].attributes;
            // add the oid field
            obj['oid'] = objList[i].oid;
            result.push(obj);
          }
          // set the total for the next callback
          result.total = results.totalCount;
        }
      }
      return result;
    });
    // define deferred.total, because otherwise it will
    // be defined by QueryResults
    deferred.total = Deferred.when(deferred, function(result) {
      return result.total;
    });

    return QueryResults(deferred);
  },

  /**
   * Get the url for this service
   * @return String
   */
  getServiceUrl: function() {
    return this.target;
  },

  /**
   * Get the url for a given object id
   * @param oid The object id
   * @return String
   */
  getObjectUrl: function(oid) {
    return '?action=detail&oid='+oid;
  },

  /**
   * Check for errors in the result and call the deferred's errback
   * method if true
   * @return Boolean True/False wether there is an error or not
   */
  handleError: function(deferred, results) {
    if (results && results.errorCode) {
      // handle errors according to errorCode
      if (results.errorCode == "CONCURRENT_UPDATE") {
      var store = wcmf.persistence.Store.getStore(this.modelClass, this.language);
      store.notify(results.errorData.currentState.attributes, results.oid);
      /*
      store.fetchItemByIdentity({
        identity: results.oid,
        onItem: function(item) {
        if (item) {
          //var itemValues = store.getA(item, 'values');
          itemValues.push(results.errorData.currentState.attributes);
          dojo.mixin(existingObj,results);
          store.setValues(item, 'values', itemValues);
        }
        }
      });
      */
      }
      deferred.errback(new Error(results.errorMessage));
      return true;
    }
    return false;
  }
});

return wcmf.persistence.DionysosServiceAdapter;
});
