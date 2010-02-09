{* this template requires the following variables: rootType *}
<ul>
{configvalue key="rootTypes" section="cms" varname="rootTypes"}
{foreach item=type from=$rootTypes}
  {if $type != $rootType}
    <li><a href="javascript:setVariable('rootType', '{$type}'); setContext('{$type}'); doDisplay(''); submitAction('display');">{$nodeUtil->getDisplayNameFromType($type)}</a></li>
  {else}
    <li class="current"><a href="javascript:setVariable('rootType', '{$type}'); setContext('{$type}'); doDisplay(''); submitAction('display');">{$nodeUtil->getDisplayNameFromType($type)}</a></li>
  {/if}
{/foreach}
</ul>