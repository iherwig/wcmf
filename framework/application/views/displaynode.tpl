{$mapper=$typeTemplate->getMapper()}
<div id="leftcol">
  <div class="contentblock">
    <fieldset>
      <legend>{if $object}{$object->getDisplayValue()}{else}{$typeTemplate->getDisplayValue(true)}{/if}</legend>
      <ol>
    {foreach item=attribute from=$mapper->getAttributes(array('DATATYPE_IGNORE'), 'none')}
        <li>
	      <label for="{$attribute->name}" title="{$typeTemplate->getValueDescription($attribute->name)}">{$typeTemplate->getValueDisplayName($attribute->name)}</label>
    	  {input name=$attribute->name inputType=$attribute->inputType value="{if $object}{$object->getValue($attribute->name)}{/if}" editable=$attribute->isEditable}
        </li>
    {/foreach}
     </ol>
   </div>
</div>
<div id="rightcol">
  {foreach item=relation from=$mapper->getRelations()}
  <div class="contentblock">
    <div id="{$relation->otherRole}Grid" style="border:1px solid #99bbe8;overflow: hidden; width: 445px"></div>
  </div>
  {/foreach}
</div>