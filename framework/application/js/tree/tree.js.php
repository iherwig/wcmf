/**
 * @class wcmf.tree.Tree. Build on Ext.tree.TreePanel
 */
wcmf.tree.Tree = function(config) {
    /**
     * @cfg {Array} customParams An assoziative array of additional values passed to the controller [optional]
     */
    Ext.apply(this, config);
    
    // create the root node
    var root = new Ext.tree.AsyncTreeNode({
      text:Message.get("Root"),
      draggable:false,
      id:'root'
    });

    // add default config properties
    config.useArrows = true,
    config.autoScroll = true,
    config.animate = true,
    config.containerScroll = true,
    config.root = root,
    config.loader = new wcmf.tree.TreeLoader({
      dataUrl:'<?php echo $APP_URL; ?>',
      baseParams:{controller:'<?php echo $controller; ?>', context:'<?php echo $context; ?>', action:'loadChildren', response_format:'JSON', sid:'<?php echo session_id() ?>'}
    })
    
    wcmf.tree.Tree.superclass.constructor.call(this, config);

    // add custom parameters to the baseParams of the TreeLoader
    if (this.customParams)
      for (var i in this.customParams)
        this.loader.baseParams.add(this.customParams[i]);
};

Ext.extend(wcmf.tree.Tree, Ext.tree.TreePanel, {
});

/**
 * @class wcmf.tree.TreeLoader. Build on Ext.tree.TreeLoader
 */
wcmf.tree.TreeLoader = function(config) {
  wcmf.tree.TreeLoader.superclass.constructor.call(this, config);
} 
Ext.extend(wcmf.tree.TreeLoader, Ext.tree.TreeLoader, {
  processResponse : function(response, node, callback) {
    var responseArray = Ext.decode(response.responseText);

    try {
      for(var i=0; i<responseArray['objects'].length; i++) {
        var responseNode = responseArray['objects'][i];
        var nodeDef = {
          'text':responseNode.text,
          'id':responseNode.oid,
          'leaf':!responseNode.hasChildren,
          'qtip':'',
          'qtipTitle':responseNode.oid,
          'cls':responseNode.hasChildren ? 'folder' : 'file',
          'href':responseNode.onClickAction
        }
        var n = this.createNode(nodeDef);
        if(n) {
          node.appendChild(n);
        }
      }
      if(typeof callback == "function") {
        callback(this, node);
      }
    } catch(e) {
      this.handleFailure(response);
    }
  }
});
