dojo.provide("wcmf.model.meta");

/**
 * Base class for all model classes
 */
dojo.declare("wcmf.model.meta.Node", null, {

  /**
   * The name of the model class
   */
  name: '',
  /**
   * Indicates wether this class is a root type
   */
  isRootType: false,
  /**
   * An array of attribute definitions
   */
  attributes: [],
  /**
   * An array of relation definitions
   */
  relations: [],
  /**
   * An array of attribte names that are used when displaying instances
   */
  displayValues: [],

  /**
   * Get the wcmf.model.meta.Node for a given role name
   * @param roleName The name of the role
   * @return wcmf.model.meta.Node
   */
  getTypeForRole: function(roleName) {
    for (var i=0, count=this.relations.length; i<count; i++) {
      if (this.relations[i].name == roleName) {
        return wcmf.model.meta.Model.getType(this.relations[i].type);
      }
    }
    return null;
  }
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