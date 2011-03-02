{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript" src="main.php?action=model"></script>
<script type="text/javascript">
dojo.addOnLoad(function() {
  /**
   * Create a grid for each root type
   */
{foreach item=tpl from=$rootTypeTemplates}
{$type=$tpl->getType()}
  var {$type}Grid = new wcmf.ui.Grid({
    id: "gridRootType{$type}",
    modelClass: wcmf.model.{$type}
  }, dojo.byId("gridRootType{$type}Div"));
  {$type}Grid.startup();
  {$type}Grid.initEvents();

{/foreach}
});

/**
 * Fix not shown grids on initially hidden tabs
 */
function startGrid(gridId) {
  var grid = dijit.byId(gridId);
  if (grid) {
    grid.resize();
  }
}
</script>
{/block}

{block name=content}
<div id="modeTabContainer" dojoType="dijit.layout.TabContainer" style="width:100%; height:100%;">
{foreach $rootTypeTemplates as $tpl}
{$type=$tpl->getType()}
{$displayName=$tpl->getObjectDisplayName()}
  <div dojoType="dijit.layout.ContentPane" title="{$displayName}">
    <div dojoType="dijit.layout.BorderContainer" style="width:100%; height:100%" gutters="false">
      <div dojoType="dijit.layout.ContentPane" region="top">
        <button dojoType="dijit.form.Button" type="button">{translate text="Create %1%" r0=$displayName}
          <script type="dojo/method" event="onClick" args="evt">wcmf.Action.create('{$type}')</script>
        </button>
      </div>
      <div dojoType="dijit.layout.ContentPane" region="center" onShow="startGrid('gridRootType{$type}');">
	    <div id="gridRootType{$type}Div" style="width:100%; height:100%;"></div>
      </div>
    </div>
  </div>
{/foreach}
</div>
{/block}