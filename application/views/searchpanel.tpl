{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body onLoad="window.opener.name='openerwindow';">
<div id="page">
{include file="lib:application/views/include/formheader.tpl" target="openerwindow"}

<div class="contentblock">
  <h3>{translate text="search for"}</h3>
  {$formUtil->getInputControl("type", "select[onchange=\"setTarget(''); submitAction('definesearch');\"]#fix:$listBoxStr", $type, true)}
</div>

{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

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
{assign var="data_types" value=$node->getDataTypes()}
{section name=data_type_index loop=$data_types}
  {assign var="cur_data_type" value=$data_types[data_type_index]}
  {if $cur_data_type != DATATYPE_IGNORE}
    {assign var="value_names" value=$node->getValueNames($cur_data_type)}
    {section name=value_name_index loop=$value_names}
      {assign var="cur_value_name" value=$value_names[value_name_index]}
  <span class="dottedSeparator"></span>
  <span class="left">{translate text=$cur_value_name}</span>
	<span class="right">{$formUtil->getInputControl($nodeUtil->getInputControlName($node, $cur_value_name, $cur_data_type), 
    $node->getValueProperty($cur_value_name, 'input_type', $cur_data_type), '', true)}</span>
    {/section}
  {/if}
{/section}
	<span class="spacer"></span>
</div>
</div>
{/if}

<div class="contentblock">
  <span class="spacer"></span>
</div>

{include file="lib:application/views/include/footer.tpl"}
