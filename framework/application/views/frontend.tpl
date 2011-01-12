{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript" src="main.php?action=getModel"></script>
<script type="text/javascript">
dojo.addOnLoad(function() {
  /**
   * Create a grid for each root type
   */
{foreach $rootTypeTemplates as $tpl}
{$type=$tpl->getType()}
  var {$type}Grid = new wcmf.ui.Grid({
    modelClass: wcmf.model.{$type}
  }, dojo.byId("grid{$type}Div"));
  {$type}Grid.startup();
  {$type}Grid.initEvents();

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
{foreach $rootTypeTemplates as $tpl}
{$type=$tpl->getType()}
{$displayName=$tpl->getObjectDisplayName()}
  <div dojoType="dijit.layout.ContentPane" title="{$displayName}" selected="true">
    <div dojoType="dijit.layout.BorderContainer" style="width:100%; height:100%" gutters="false">
      <div dojoType="dijit.layout.ContentPane" region="top">
        <button dojoType="dijit.form.Button" type="button">{translate text="Create %1%" r0=$displayName}
          <script type="dojo/method" event="onClick" args="evt">wcmf.Action.create('{$type}')</script>
        </button>
      </div>
      <div dojoType="dijit.layout.ContentPane" region="center">
	    <div id="grid{$type}Div" style="width:100%; height:100%;"></div>
      </div>
    </div>
  </div>
{/foreach}
</div>
{/block}