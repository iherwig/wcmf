define([
    "dojo/_base/xhr",
    "dojo/_base/lang",
    "dojo/_base/declare",
    "dojo/store/JsonRest",
    "dojo/store/Cache",
    "dojo/store/Memory",
    "dojo/store/util/QueryResults"
], function (
    xhr,
    lang,
    declare,
    JsonRest,
    Cache,
    Memory,
    QueryResults
) {
    var Store = declare([JsonRest], {

      query: function(query, options){
        options = options || {};

        var headers = lang.mixin({ Accept: this.accepts }, this.headers, options.headers);

        if(options.start >= 0 || options.count >= 0){
          headers.Range = headers["X-Range"] //set X-Range for Opera since it blocks "Range" header
             = "items=" + (options.start || '0') + '-' +
            (("count" in options && options.count != Infinity) ?
              (options.count + (options.start || 0) - 1) : '');
        }
        var hasQuestionMark = this.target.indexOf("?") > -1;
        if(query && typeof query == "object"){
          query = xhr.objectToQuery(query);
          query = query ? (hasQuestionMark ? "&" : "?") + query: "";
        }
        if(options && options.sort){
          var sortParam = this.sortParam;
          query += (query || hasQuestionMark ? "&" : "?") + (sortParam ? sortParam + '=' : "sort(");
          for(var i = 0; i<options.sort.length; i++){
            var sort = options.sort[i];
            query += (i > 0 ? "," : "") + (sort.descending ? this.descendingPrefix : this.ascendingPrefix) + encodeURIComponent(sort.attribute);
          }
          if(!sortParam){
            query += ")";
          }
        }
        var results = xhr("GET", {
          url: this.target + (query || ""),
          handleAs: "json",
          headers: headers,
          load: function(response, ioArgs) {
            var objects = [];
            // extruct objects from response
            if (response.list) {
              for (var i=0, count=response.list.length; i<count; i++) {
                objects.push(response.list[i].attributes);
              }
            }
            return objects;
          }
        });
        results.total = results.then(function(){
          var range = results.ioArgs.xhr.getResponseHeader("Content-Range");
          return range && (range = range.match(/\/(.*)/)) && +range[1];
        });
        return QueryResults(results);
      }
    });

    /**
     * Registry for shared instances
     */
    Store.instances = {};

    /**
     * Get the store for a given type and language
     * @param typeName The name of the type
     * @param language The language
     * @return Store instance
     */
    Store.getStore = function(typeName, language) {
        if (!Store.instances[typeName]) {
            Store.instances[typeName] = {};
        }
        if (!Store.instances[typeName][language]) {
            var store = Cache(
                Store({target: appConfig.pathPrefix+"/rest/"+language+"/"+typeName/*+"/?XDEBUG_SESSION_START=netbeans-xdebug"*/}),
                Memory()
            );
            Store.instances[typeName][language] = store;
        }
        return Store.instances[typeName][language];
    };

    return Store;
});