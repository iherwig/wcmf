dojo.provide("wcmf.model.meta.Model");

/**
 * The meta model is a description the domain model
 */
dojo.declare("wcmf.model.meta.Model", null, {
});

/**
 * Register a type
 * @param typeInstance An instance of a wcmf.model.meta.Node subclass
 */
wcmf.model.meta.Model.registerType = function(typeInstance) {
  wcmf.model.meta.Model.types[typeInstance.name] = typeInstance;
};

/**
 * Get a type from it's name
 * @param name The name of the type
 * @return wcmf.model.meta.Node instance
 */
wcmf.model.meta.Model.getType = function(typeName) {
  return wcmf.model.meta.Model.types[typeName];
};

/**
 * Get a type from an object id
 * @param name The name of the type
 * @return wcmf.model.meta.Node instance
 */
wcmf.model.meta.Model.getTypeFromOid = function(oid) {
  var typeName = wcmf.model.meta.Node.getTypeFromOid(oid);
  return wcmf.model.meta.Model.types[typeName];
};

/**
 * Get all types that are defined in the meta model
 * @return An array of wcmf.model.meta.Node instances
 */
wcmf.model.meta.Model.getAllTypes = function() {
  var types = [];
  for (var typeName in wcmf.model.meta.Model.types) {
    types.push(wcmf.model.meta.Model.types[typeName]);
  }
  return types;
};

wcmf.model.meta.Model.types = {};