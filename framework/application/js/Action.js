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
  }, 'mainForm').addCallback(function(data) {
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
wcmf.Action.save = function() {
  var tabContainer = dijit.byId("modeTabContainer");
  if (tabContainer) {
	// save the current DetailPane
	var pane = tabContainer.selectedChildWidget;
	if (pane instanceof wcmf.ui.DetailPane) {
	  pane.save();
	}
  }
};

/**
 * The create action
 */
wcmf.Action.create = function(type) {
  var tabContainer = dijit.byId("modeTabContainer");
  if (tabContainer) {
	var pane = new wcmf.ui.DetailPane({
      title: wcmf.Message.get("New %1%", [type]),
      oid: null,
      modelClass: wcmf.model[type],
      href: '?action=getDetail&type='+type
    });
	tabContainer.addChild(pane);
	tabContainer.selectChild(pane);
  }
};

/**
 * The edit action
 */
wcmf.Action.edit = function(type, oid) {
  var tabContainer = dijit.byId("modeTabContainer");
  if (tabContainer) {
	var pane = new wcmf.ui.DetailPane({
      title: oid,
      oid: oid,
      modelClass: wcmf.model[type],
      href: '?action=getDetail&oid='+oid
	});
	tabContainer.addChild(pane);
	tabContainer.selectChild(pane);
  }
};
