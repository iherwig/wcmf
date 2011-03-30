{* This template is assumed to be loaded as content of a wcmf.ui.DetailPane *}

{$mapper=$typeTemplate->getMapper()}
<script type="text/javascript">
dojo.addOnLoad(function() {

  var detailNode = dojo.byId("detail{$object->getOID()}");
  var detailPane = dijit.getEnclosingWidget(detailNode.parentNode);
  if (!detailPane.getIsNewNode()) {
    detailPane.set("title", "{$object->getDisplayValue()}");
  }

  var relationTabContainer = new dijit.layout.TabContainer({
    useMenu: true,
    tabPosition: "bottom"
  }, dojo.byId("relationTabContainer{$object->getOID()}Div"));

  /**
   * Create a grid for each related type
   */
{foreach item=relation from=$mapper->getRelations()}
{$type=$relation->getOtherType()}
{$role=$relation->getOtherRole()}
  var {$role}Pane = new dijit.layout.ContentPane({
    title: '{$role}'
  });
  var {$role}Grid = new wcmf.ui.Grid({
    modelClass: wcmf.model.{$type},
    query: {
      query: "{$obfuscator->obfuscate($nodeUtil->getRelationQueryCondition($object, $role))}"
    }
  });
  {$role}Pane.set('content', {$role}Grid);
  {$role}Grid.initEvents();
  relationTabContainer.addChild({$role}Pane);

{/foreach}

  relationTabContainer.startup();
});
</script>

<div data-dojo-type="dijit.layout.BorderContainer" id="detail{$object->getOID()}" style="padding:0; width:100%; height:100%" data-dojo-props="gutters:false">
  <!-- CENTER Pane -->
  <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'leading'" style="padding:0; width:370px;">
    <div class="wcmf_form">
      <fieldset>
        <!--legend>{$object->getDisplayValue()}</legend-->
        <ol>
      {foreach item=attribute from=$mapper->getAttributes(array('DATATYPE_IGNORE'), 'none')}
      {$attributeName=$attribute->getName()}
          <li>
            <label for="{$attributeName}" title="{$typeTemplate->getValueDescription($attributeName)}">{$typeTemplate->getValueDisplayName($attributeName)}</label>
            {input object=$object name=$attributeName}
          </li>
      {/foreach}
        </ol>
      </fieldset>
    </div>
  </div>
  <!-- RIGHT Pane -->
  <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'center'" style="padding:0;">
    <div id="relationTabContainer{$object->getOID()}Div"></div>
  </div>
</div>