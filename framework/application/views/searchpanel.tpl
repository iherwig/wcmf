{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body onLoad="window.opener.name='openerwindow';">
<div id="page">
{include file="lib:application/views/include/formheader.tpl" target="openerwindow"}

<div class="contentblock">
  <h3>{translate text="search for"}</h3>
  {input name="type" type="select[onchange=\"setTarget(''); submitAction('definesearch');\"]#fix:$listBoxStr" value=$type editable=true}
</div>

{include file="lib:application/views/include/error.tpl"}

<div id="navcontent">
  <ul>
    <li><a href="javascript:setTarget('openerwindow'); submitAction('search');">{translate text="Search"}</a></li>
    <li><a href="javascript:setTarget(''); submitAction('definesearch');">{translate text="Clear Details"}</a></li>
  </ul>
</div>

{if $node}
<div id="leftcol">
<div class="contentblock">
	<h2>{$node->getObjectDisplayName()}&nbsp;</h2>
	<span class="spacer"></span>
{assign var="value_names" value=$node->getValueNames()}
{section name=value_name_index loop=$value_names}
  {assign var="cur_value_name" value=$value_names[value_name_index]}
  <span class="dottedSeparator"></span>
  <span class="left">{translate text=$cur_value_name}</span>
	<span class="right">{input node=$node property=$cur_value_name value='' editable=true}</span>
{/section}
	<span class="spacer"></span>
</div>
</div>
{/if}

<div class="contentblock">
  <span class="spacer"></span>
</div>

{include file="lib:application/views/include/footer.tpl"}
