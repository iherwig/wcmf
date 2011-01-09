{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body onLoad="top.adjustIFrameSize('{$type}ChildrenIFrame');">
<div id="subpage">
{include file="lib:application/views/include/formheader.tpl"}
<input type="hidden" name="type" value="{$type}">
<input type="hidden" name="canCreate" value="{$canCreate}">
<input type="hidden" name="composition" value="{$composition}">

<div class="sublist">
	{if $canCreate}
    {if !$subjectType}
      {assign var="subjectType" value=$type}
    {/if}
	<span class="associatepanel">
    {if $composition || $aggregation}
    <a href="javascript:doSetParent('{$poid}'); doNew('{$subjectType}'); setContext('{$subjectType}'); submitAction('new');" target="_parent" title="{translate text="Create"}"><img src="images/new.png" alt="{translate text="Create new %1%" r1=$nodeUtil->getDisplayNameFromType($subjectType)}" title="{translate text="Create new %1%" r1=$nodeUtil->getDisplayNameFromType($subjectType)}" border="0"></a></span>
    {/if}
    {if !$composition}
		{input name="associateTo" type="select#fkt:g_getObjects|$subjectType,$poid" value="" editable=true}
		<a href="javascript:setVariable('associateoids', document.getElementById('{$type}ChildrenIFrame').contentWindow.document.forms[0].associateTo.value); submitAction('associate');" target="_parent" title="{translate text="Associate selected"}"><img src="images/link.png" alt="{translate text="Associate selected"}" title="{translate text="Associate selected"}" border="0"></a>
    {else}
    &nbsp;
    {/if}
	</span>
	{/if}
</div>

<div class="sublist">
{include file="lib:application/views/include/list_navigation.tpl"}
{include file="lib:application/views/include/error.tpl"}

<table width="450">
{section name=node_index loop=$nodes}
{assign var="node" value=$nodes[node_index]}
{if $node->getProperty('realSubject')}
  {assign var="subject" value=$node->getProperty('realSubject')}
{else}
  {assign var="subject" value=$node}
{/if}
	{cycle values="light,dark" assign="style"}
  <tr onmouseover="this.className='hlt'" onmouseout="this.className=''" onclick="parent.setContext('{$subject->getType()}'); parent.doDisplay('{$subject->getOID()}'); parent.submitAction('display');">
    {foreach key=name item=value from=$subject->getDisplayValues(true)}
    <td class="row{$style}">{$value}</td>
    {/foreach}
  	  <td class="row{$style}" width="100">
      {include file="lib:application/views/include/sort_item.tpl" node=$node}
      <a href="javascript:setContext('{$subject->getType()}'); doDisplay('{$subject->getOID()}'); submitAction('display')" target="_parent" title="{translate text="Edit %1%" r1=$subject->getOID()}"><img src="images/edit.png" alt="{translate text="Edit %1%" r1=$subject->getOID()}" title="{translate text="Edit %1%" r1=$subject->getOID()}" border="0"></a>
	{if $canCreate}
      <a href="javascript:setVariable('oid', '{$subject->getOID()}'); setVariable('targetoid', '{$poid}'); submitAction('copy')" target="_parent" title="{translate text="Duplicate %1%" r0=$subject->getOID()}"><img src="images/duplicate.png" alt="{translate text="Duplicate %1%" r1=$subject->getOID()}" title="{translate text="Duplicate %1%" r1=$subject->getOID()}" border="0"></a>
  {/if}
  {if $composition}
      <a href="javascript:if(doDelete('{$subject->getOID()}', true, '{translate text="Really delete node %1%?" r1=$subject->getOID()}')) submitAction('delete');" target="_parent" title="{translate text="Delete %1%" r1=$subject->getOID()}"><img src="images/delete.png" alt="{translate text="Delete %1%" r1=$subject->getOID()}" title="{translate text="Delete %1%" r1=$subject->getOID()}" border="0"></a>
  {else}
      <a href="javascript:setVariable('oid', '{$poid}'); setVariable('associateoids', '{$subject->getOID()}'); submitAction('disassociate');" target="_parent" title="{translate text="Disassociate %1%" r1=$subject->getOID()}"><img src="images/unlink.png" alt="{translate text="Disassociate %1%" r1=$subject->getOID()}" title="{translate text="Disassociate %1%" r1=$subject->getOID()}" border="0"></a>
  {/if}
    </td>
  </tr>
{sectionelse}
  <tr><td><h3>{translate text="none"}</h3></td></tr>
{/section}
</table>
</div>

{include file="lib:application/views/include/footer.tpl" addSpacer=false}
