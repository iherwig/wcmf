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

      idProperty: 'oid'

      // NOTE: use dojo/request/notify to intercept communication with server
      // http://dojotoolkit.org/reference-guide/1.8/dojo/request/notify.html#dojo-request-notify

      // TODO:
      // implement DojoNodeSerializer on server that uses refs
      // http://dojotoolkit.org/reference-guide/1.7/dojox/json/ref.html#dojox-json-ref
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