{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript" src="main.php?action=model"></script>
<script type="text/javascript">
dojo.addOnLoad(function() {
  /**
   * Create stores and grids for each root node
   */
{foreach $rootNodeTemplates as $node}
{$type=$node->getType()}
  wcmf.persistence.Store.create(wcmf.model.{$type});
  new wcmf.ui.Grid({
    modelClass: wcmf.model.{$type}
  }, dojo.byId("grid{$node->getType()}Div")).startup();
  
{/foreach}
});

// TODO: remove this, for testing only
function test() {
  var store = wcmf.persistence.Store.getStore("Page");
  store.fetchItemByIdentity({
      identity: "Page:1",
      onItem: function(item) {
        store.setValue(item, "name", "TEST");
      }
  });
}
</script>
{/block}

{block name=content}
<div id="modeTabContainer" dojoType="dijit.layout.TabContainer" style="width:100%; height:100%;">
{foreach $rootNodeTemplates as $node}
  <div dojoType="dijit.layout.ContentPane" title="{$node->getObjectDisplayName()}" selected="true">
    <div dojoType="dijit.layout.BorderContainer" style="width:100%; height:100%" gutters="false">
      <div dojoType="dijit.layout.ContentPane" region="top">
        <button dojoType="dijit.form.Button" type="button">{translate text="Create %1%" r0=$node->getObjectDisplayName()}
          <script type="dojo/method" event="onClick" args="evt">wcmf.Action.create('{$node->getType()}')</script>
        </button>
      </div>
      <div dojoType="dijit.layout.ContentPane" region="center">
	    <div id="grid{$node->getType()}Div" style="width:100%; height:100%;"></div>
      </div>
    </div>
  </div>
{/foreach}
</div>
{/block}