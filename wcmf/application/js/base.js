dojo.provide("app.base");

dojo.ready(function() {

  // dojo
  dojo.require('dojo.parser');
  dojo.require("dojo.fx");

  dojo.require("dijit.form.Form");
  dojo.require("dijit.form.Button");
  dojo.require("dijit.form.ToggleButton");
  dojo.require("dijit.form.TextBox");
  dojo.require("dijit.form.ValidationTextBox");
  dojo.require("dijit.form.Textarea");
  dojo.require("dijit.form.CheckBox");
  dojo.require("dijit.form.RadioButton");
  dojo.require("dijit.form.FilteringSelect");
  dojo.require("dijit.form.DateTextBox");
  dojo.require("dijit.layout.BorderContainer");
  dojo.require("dijit.layout.ContentPane");
  dojo.require("dijit.MenuBar");
  dojo.require("dijit.MenuBarItem");
  dojo.require("dijit.MenuItem");
  dojo.require("dijit.Tree");
  dojo.require("dijit.tree.ForestStoreModel");

  dojo.require("dojox.uuid.generateRandomUuid");
  dojo.require("dojox.layout.ToggleSplitter");

  // ibm
  dojo.require("com.ibm.developerworks.EasyRestService");

  // wcmf
  dojo.require("wcmf.Error")
  dojo.require("wcmf.Action");
  dojo.require("wcmf.model.meta.Model");
  dojo.require("wcmf.model.meta.Node");

  dojo.require("wcmf.persistence.Request");
  dojo.require("wcmf.persistence.DionysosService");
  dojo.require("wcmf.persistence.Store");

  dojo.require("wcmf.ui.TypeTabContainer");
  dojo.require("wcmf.ui.NodeTabContainer");
  dojo.require("wcmf.ui.Form");
  dojo.require("wcmf.ui.Grid");
  dojo.require("wcmf.ui.GridActionCell");
  dojo.require("wcmf.ui.Format");
  dojo.require("wcmf.ui.DetailPane");
  dojo.require("wcmf.ui.AttributePane");
  dojo.require("wcmf.ui.RelationTabContainer");
  dojo.require("wcmf.ui.RelationPane");
  dojo.require("wcmf.ui.ObjectSelectDialog");
  dojo.require("wcmf.ui.CkEditorWidget");
  dojo.require("wcmf.ui.ObjectTree");

  // create declarative widgets after code is loaded
  dojo.parser.parse();
});
