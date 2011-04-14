{* This template is assumed to be loaded as content of a wcmf.ui.DetailPane *}

{uniqueid varname="uid"}
{$mapper=$typeTemplate->getMapper()}
<script type="text/javascript">
dojo.addOnLoad(function() {

  // get the DetailPane instance
  var detailNode = dojo.byId("detail{$uid}Div");
  var detailPane = dijit.getEnclosingWidget(detailNode.parentNode);

  // update the title of the pane, if it is an existing object
  if (!detailPane.getIsNewNode()) {
    detailPane.set("title", "{$object->getDisplayValue()}");
  }

  // create a RelationTabContainer with one RelationPane instance for each related type
  var relations = [];
{foreach item=relation from=$mapper->getRelations()}
  {$role=$relation->getOtherRole()}
  relations["{$role}"] = "{$obfuscator->obfuscate($nodeUtil->getRelationQueryCondition($object, $role))}";
{/foreach}

  var relationTabContainer = new wcmf.ui.RelationTabContainer({
    oid: "{$object->getOid()}",
    relations: relations
  }, dojo.byId("relationTabContainer{$uid}Div"));
  relationTabContainer.startup();
});
</script>

<div data-dojo-type="dijit.layout.BorderContainer" id="detail{$uid}Div" style="padding:0; width:100%; height:100%" data-dojo-props="gutters:false">
  <!-- CENTER Pane -->
  <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'leading'" style="padding:0; width:450px;">
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
    <div id="relationTabContainer{$uid}Div"></div>
  </div>
</div>