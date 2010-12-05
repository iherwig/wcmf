Ext.namespace('wcmf', 'wcmf.grid', 'wcmf.tree');

Ext.MessageBox.buttonText.yes = Message.get("Yes");
Ext.MessageBox.buttonText.no = Message.get("No");

/**
 * @class Action
 */
Action = function() {};

/**
 * Perform a wcmf action as Ajax request
 * @param action The name of the action
 * @param customParams An object defining custom parameters to be passed to the next controller
 * @param callback The function to call after execution
 * @param scope The scope in which to execute the callback function. The handler function's "this" context.
 */
Action.perform = function(action, customParams, callback, scope) {
  // define the call parameters
  var callParams = {controller:'<?php echo $controller; ?>', context:'<?php echo $context; ?>', action:action, sid:'<?php echo session_id() ?>'};
  for (var i in customParams)
    callParams[i] = customParams[i];
    
  // create the proxy and do the request
  var proxy = new Ext.data.HttpProxy({
        url: '<?php echo $APP_URL; ?>'
  });
  proxy.load(callParams, null, callback, scope);
};
