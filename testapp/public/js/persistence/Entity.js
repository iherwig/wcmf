define([
    "dojo/_base/lang",
    "dojo/when",
    "dojo/Stateful"
], function(
    lang,
    when,
    Stateful
){
    var Entity = function(results) {

	if(!results){
		return results;
	}
	// if it is a promise it may be frozen
	if(results.then){
		results = lang.delegate(results);
	}
        results = when(results, function(results) {
                var stateful = new Stateful();
                for (var key in results) {
                    stateful.set(key, results[key]);
                }
                return stateful;
        });
	return results; // Object
    };

    return Entity;
});
