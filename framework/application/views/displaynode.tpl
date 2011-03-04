{* This template is assumed to be loaded as content of a wcmf.ui.DetailPane *}

{$mapper=$typeTemplate->getMapper()}
<script type="text/javascript">
dojo.addOnLoad(function() {

  var detailNode = dojo.byId("detail{$object->getOID()}");
  var detailPane = dijit.getEnclosingWidget(detailNode.parentNode);
  if (detailPane.oid) {
    detailPane.set("title", "{$object->getDisplayValue()}");
  }

  /**
   * Create a grid for each related type
   */
{foreach item=relation from=$mapper->getRelations()}
{$type=$relation->getOtherType()}
{$role=$relation->getOtherRole()}
  var {$role}Grid = new wcmf.ui.Grid({
    modelClass: wcmf.model.{$type},
    autoHeight: 10,
    rowsPerPage: 10,
    rowCount: 10
  }, dojo.byId("grid{$object->getOID()}{$role}Div"));
  {$role}Grid.startup();
  {$role}Grid.initEvents();

{/foreach}
});
</script>

<div id="detail{$object->getOID()}">
  <div class="leftcol">
    <div class="contentblock">
      <fieldset>
        <legend>{$object->getDisplayValue()}</legend>
        <ol>
      {foreach item=attribute from=$mapper->getAttributes(array('DATATYPE_IGNORE'), 'none')}
      {$attributeName=$attribute->getName()}
          <li>
            <label for="{$attributeName}" title="{$typeTemplate->getValueDescription($attributeName)}">{$typeTemplate->getValueDisplayName($attributeName)}</label>
            {input object=$object name=$attributeName}
          </li>
      {/foreach}
       </ol>
     </div>
  </div>
  <div class="rightcol">
    {foreach item=relation from=$mapper->getRelations()}
    <h2>{$relation->getOtherRole()}</h2>
    <div id="grid{$object->getOID()}{$relation->getOtherRole()}Div" style="width:445px; height:200px;"></div>
    {/foreach}
  </div>
</div>