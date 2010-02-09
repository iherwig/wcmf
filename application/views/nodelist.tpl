{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body onLoad="top.adjustIFrameSize('{$type}IFrame');">
<div id="subpage">
{include file="lib:application/views/include/formheader.tpl"}
<input type="hidden" name="type" value="{$type}">

<div class="sublist">
{include file="lib:application/views/include/list_navigation.tpl"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<table width="600">
{section name=node_index loop=$nodes}
{assign var="node" value=$nodes[node_index]}
	{cycle values="light,dark" assign="style"}
  <tr onmouseover="this.className='hlt'" onmouseout="this.className=''" onclick="parent.setContext('{$node->getType()}'); parent.doDisplay('{$node->getOID()}'); parent.submitAction('display');">
    {foreach key=name item=value from=$node->getDisplayValues(true)}
    <td class="row{$style}">{$value}</td>
    {/foreach}
    <td class="row{$style}" width="90">
    	{include file="lib:application/views/include/sort_item.tpl" node=$node}
      <a href="javascript:setContext('{$node->getType()}'); doDisplay('{$node->getOID()}'); submitAction('display')" target="_parent" class="{$style}" title="{translate text="Edit %1%" r1=$node->getOID()}"><img src="images/edit.png" alt="{translate text="Edit %1%" r1=$node->getOID()}" title="{translate text="Edit %1%" r1=$node->getOID()}" border="0"></a>
      <a href="javascript:setVariable('oid', '{$node->getOID()}'); setVariable('targetoid', '{translate text="Root"}'); submitAction('copy')" title="{translate text="Duplicate %1%" r0=$node->getOID()}"><img src="images/duplicate.png" alt="{translate text="Duplicate %1%" r1=$node->getOID()}" title="{translate text="Duplicate %1%" r1=$node->getOID()}" border="0"></a>
      <a href="javascript:if(doDelete('{$node->getOID()}', true, '{translate text="Really delete node %1%?" r1=$node->getOID()}')) submitAction('delete');" class="{$style}" title="{translate text="Delete %1%" r1=$node->getOID()}"><img src="images/delete.png" alt="{translate text="Delete %1%" r1=$node->getOID()}" title="{translate text="Delete %1%" r1=$node->getOID()}" border="0"></a>
    </td>
  </tr>
{sectionelse}
  <tr><td><h3>{translate text="none"}</h3></td></tr>
{/section}
</table>
</div>

<div class="sublist">
{assign var="displayName" value=$nodeUtil->getDisplayNameFromType($type)}
  <span class="left"><a href="javascript:doSetParent(''); doNew('{$type}'); submitAction('new');" target="_parent" title="{translate text="Create new %1%" r1=$displayName}"><img src="images/new.png" alt="{translate text="Create new %1%" r1=$displayName}" title="{translate text="Create new %1%" r1=$displayName}" border="0"></a></span>
</div>

{include file="lib:application/views/include/footer.tpl" addSpacer=false}
