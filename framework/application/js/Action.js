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
 * The create action
 */
wcmf.Action.create = function(type) {
  var tabContainer = dijit.byId("modeTabContainer");
  if (tabContainer) {
	var pane = new dijit.layout.ContentPane({
      title: wcmf.Message.get("New %1%", [type]),
      closable: true,
      onClose: function() {
        // confirm() returns true or false, so return that.
        return confirm(wcmf.Message.get("Do you really want to close this?"));
      }
    });
	tabContainer.addChild(pane);
  }
};
