dojo.provide("wcmf.base");

dojo.ready(function() {
  
  // dojo
  dojo.require('dojo.parser');

  dojo.require("dijit.form.Form");
  dojo.require("dijit.form.Button");
  dojo.require("dijit.form.ValidationTextBox");
  dojo.require("dijit.form.CheckBox");
  dojo.require("dijit.layout.BorderContainer");
  dojo.require("dijit.layout.ContentPane");
  dojo.require("dijit.layout.TabContainer");
  dojo.require("dijit.MenuBar");
  dojo.require("dijit.MenuBarItem");
  dojo.require("dijit.MenuItem");
  dojo.require("dijit.Toolbar");
  dojo.require("dojo.fx");
  dojo.require('dojox.uuid.generateRandomUuid');
  
  // wCMF
  dojo.require('wcmf.Action');
/*  <script type="text/javascript" src="{$libDir}application/js/Error.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/model/meta/Model.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/model/meta/Node.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/EasyRestService.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/Request.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/DionysosService.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/Store.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/TypeTabContainer.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/NodeTabContainer.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/Grid.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/GridActionCell.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/DetailPane.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/RelationTabContainer.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/RelationPane.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/ObjectSelectDialog.js"></script>*/
  
  // create declarative widgets after code is loaded
  dojo.parser.parse();
});
