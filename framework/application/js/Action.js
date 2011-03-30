/**
 * @class Action
 */
dojo.declare("wcmf.Action", null, {
});

/**
 * The login action
 */
wcmf.Action.login = function() {
  wcmf.Error.hide();
  new wcmf.persistence.Request().sendAjax({
    action:'dologin',
    responseFormat:'json'
  }, 'loginForm').addCallback(function(data) {
    // redirect on success
    top.location.href = wcmf.appURL+'?action=ok';
  });
};

/**
 * The logout action
 */
wcmf.Action.logout = function() {
    top.location.href = wcmf.appURL+'?action=logout';
};

/**
 * The save action
 */
wcmf.Action.save = function(oid) {
  var typeTabContainer = dijit.byId("nodeTabContainer");
  if (typeTabContainer) {
    // save the DetailPane with the given oid
    var nodeTabContainer = typeTabContainer.selectedChildWidget;
    var pane = nodeTabContainer.getDetailPane(oid);
    if (pane != null) {
      pane.save();
    }
  }
};

/**
 * The create action
 */
wcmf.Action.create = function(modelClass) {
  var typeTabContainer = dijit.byId("nodeTabContainer");
  if (typeTabContainer) {
    var nodeTabContainer = typeTabContainer.getNodeTabContainer(modelClass);
    typeTabContainer.selectChild(nodeTabContainer);
    nodeTabContainer.addNode(wcmf.model.meta.Node.createRandomOid(modelClass.type), true);
  }
};

/**
 * The edit action
 */
wcmf.Action.edit = function(oid) {
  var modelClass = wcmf.model.meta.Model.getType(wcmf.model.meta.Node.getTypeFromOid(oid));
  var typeTabContainer = dijit.byId("nodeTabContainer");
  if (typeTabContainer) {
    var nodeTabContainer = typeTabContainer.getNodeTabContainer(modelClass);
    typeTabContainer.selectChild(nodeTabContainer);
    nodeTabContainer.addNode(oid, false);
  }
};

/**
 * The delete action
 */
wcmf.Action.remove = function(oid) {
  if (confirm(wcmf.Message.get('Are you sure?'))) {
    var modelClass = wcmf.model.meta.Model.getType(wcmf.model.meta.Node.getTypeFromOid(oid));
    var typeTabContainer = dijit.byId("nodeTabContainer");
    if (typeTabContainer) {
      var nodeTabContainer = typeTabContainer.getNodeTabContainer(modelClass);
      nodeTabContainer.deleteNode(oid);
    }
  }
};
