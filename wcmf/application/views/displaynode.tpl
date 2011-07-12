{* This template is assumed to be loaded as content of a wcmf.ui.DetailPane *}

{uniqueid varname="uid"}
{$mapper=$typeTemplate->getMapper()}
<script type="text/javascript">
dojo.addOnLoad(function() {

  // get the DetailPane instance
  var detailPane = wcmf.ui.DetailPane.getFromContainedDiv("detail{$uid}Div");

  // create a RelationTabContainer with one RelationPane instance for each related type
  var relations = [];
{foreach $mapper->getRelations() as $relation}
  {$otherRole=$relation->getOtherRole()}

  relations.push({
    role: "{$otherRole}",
    query: "{$obfuscator->obfuscate($nodeUtil->getRelationQueryCondition($object, $otherRole))}"{if $orderBy}
{/if}
  });
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
  <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'leading'" style="padding:0; width:450px; overflow:auto">
    <div data-dojo-type="dijit.layout.TabContainer" data-dojo-props="tabPosition:'bottom'">
{configvalue key="defaultLanguage" section="i18n" varname="defaultLanguage"}
{foreach $languages as $languageKey => $languageName}
      <div data-dojo-type="wcmf.ui.AttributePane" data-dojo-props="title:'{$languageName}', language:'{$languageKey}', oid:'{$object->getOID()}', isNewNode:{if $isNew}true{else}false{/if}, {if $languageKey == $defaultLanguage}selected:true{/if}">
        <div class="wcmf_form">
          <fieldset>
            <!--legend>{$object->getDisplayValue()}</legend-->
            <ol>
          {foreach item=attribute from=$mapper->getAttributes(array('DATATYPE_IGNORE'), 'none')}
          {$attributeName=$attribute->getName()}
              <li>
                <label for="{$attributeName}" title="{$typeTemplate->getValueDescription($attributeName)}">{$typeTemplate->getValueDisplayName($attributeName)}</label>
                {input object=$typeTemplate name=$attributeName language=$languageKey}
              </li>
          {/foreach}
            </ol>
          </fieldset>
        </div>
      </div>
{/foreach}
    </div>
  </div>
  <!-- RIGHT Pane -->
  <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'center'" style="padding:0;">
    <div id="relationTabContainer{$uid}Div"></div>
  </div>
</div>
