dojo.provide("wcmf.model.meta");

/**
 * Base class for all model classes
 */
dojo.declare("wcmf.model.meta.Node", null, {
});

/**
 * Get the type parameter from an object id. Object ids have
 * the format type:id1:id2..
 * @return String
 */
wcmf.model.meta.Node.getTypeFromOid = function(oid) {
  var parts = oid.split(":", 1);
  return parts[0];
};

/**
 * Get a random unique object id for a given type
 * @param type The type
 * @return String
 */
wcmf.model.meta.Node.createRandomOid = function(type) {
  var oid = type+":"+dojox.uuid.generateRandomUuid();
  return oid;
};
