/**
 * @class Action
 *
 * Action methods are to be used on buttons to change the application
 * state. Persistent operations do not directly interact with the
 * persistent store, but with the appropriate ui elements.
 */
dojo.declare("wcmf.Action", null, {
});

/**
 * Sends the login request and redirects to the application ui on success.
 */
wcmf.Action.login = function() {
  wcmf.Error.hide();
  new wcmf.persistence.Request().sendAjax({
    action: 'dologin',
    responseFormat: 'json'
  }, 'loginForm').then(function(data) {
    // redirect on success
    top.location.href = wcmf.appURL+'?action=ok';
  });
};

/**
 * Sends the logout action and shows the login screen
 */
wcmf.Action.logout = function() {
    top.location.href = wcmf.appURL+'?action=logout';
};

/**
 * Saves the content of the DetailPane that shows the object with
 * the given object id. If no DetailPane is opened or no modifications
 * are done in the opened DetailPane, nothing happens. If DetailPane
 * contains content for a new object, it is created.
 * @param oid The object id
 * @return dojo.Deferred promise (The only parameter is the saved item)
 */
wcmf.Action.save = function(oid) {
  var deferred = new dojo.Deferred();
  var typeTabContainer = wcmf.ui.TypeTabContainer.getInstance();

  // save the DetailPane with the given oid
  wcmf.Error.hide();
  var nodeTabContainer = typeTabContainer.selectedChildWidget;
  var pane = nodeTabContainer.getDetailPane(oid);
  if (pane != null) {
    pane.save().then(function(item) {
      // do nothing on success
      deferred.callback(item);
    }, function(errorMsg) {
      wcmf.Error.show(errorMsg);
      deferred.errback(errorMsg);
    });
  }
  else {
    deferred.errback(wcmf.Message.get("The object %1% is not opened in an editor", [oid]));
  }
  return deferred.promise;
};

/**
 * Shows a new DetailPane for a new instance the given type
 * @param modelClass The type
 * @return wcmf.ui.DetailPane
 */
wcmf.Action.create = function(modelClass) {
  wcmf.Error.hide();
  var typeTabContainer = wcmf.ui.TypeTabContainer.getInstance();
  var detailPane = typeTabContainer.displayNode(
    wcmf.model.meta.Node.createRandomOid(modelClass.name), true);
  return detailPane;
};

/**
 * Shows the DetailPane containing the data of the object with the
 * given object id.
 * @param oid The object id
 * @return wcmf.ui.DetailPane
 */
wcmf.Action.edit = function(oid) {
  wcmf.Error.hide();
  var typeTabContainer = wcmf.ui.TypeTabContainer.getInstance();
  var detailPane = typeTabContainer.displayNode(oid, false);
  return detailPane;
};

/**
 * Asks for confirmation and deletes the object with the given object id if yes.
 * @param oid The object id
 */
wcmf.Action.remove = function(oid) {
  wcmf.Error.hide();
  if (confirm(wcmf.Message.get('Are you sure?'))) {
    var modelClass = wcmf.model.meta.Model.getTypeFromOid(oid);
    var typeTabContainer = wcmf.ui.TypeTabContainer.getInstance();
    var nodeTabContainer = typeTabContainer.getNodeTabContainer(modelClass);
    nodeTabContainer.deleteNode(oid);
  }
};

/**
 * Associate two objects and notifies the appropriate DetailPanes to
 * update the RelationPanes.
 * @param sourceOid The object id of the source object
 * @param targetOid The object id of the target object
 * @param role The role of the target object in relation to the source object
 */
wcmf.Action.associate = function(sourceOid, targetOid, role) {
  wcmf.Error.hide();
  new wcmf.persistence.Request().sendAjax({
    action: 'associate',
    sourceOid: sourceOid,
    targetOid: targetOid,
    role: role,
    responseFormat: 'json',
    controller: 'TerminateController'
  }, null).then(function(data) {
    // update ui
    var typeTabContainer = wcmf.ui.TypeTabContainer.getInstance();
    var sourceModelClass = wcmf.model.meta.Model.getTypeFromOid(sourceOid);
    var targetModelClass = wcmf.model.meta.Model.getTypeFromOid(targetOid);
    var sourceNodeTabContainer = typeTabContainer.getNodeTabContainer(sourceModelClass);
    var targetNodeTabContainer = typeTabContainer.getNodeTabContainer(targetModelClass);
    // TODO: update grid if showing
  });
};
