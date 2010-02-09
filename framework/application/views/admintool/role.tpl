{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body>
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div id="tabnav"></div>

{include file="lib:application/views/admintool/include/navigation.tpl"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<div id="leftcol">

{*------ Edit ------*}
<div class="contentblock">
	<h2 title="{translate text="object ID"}: {$oid|default:"-"}">{$principal->getDisplayValue(true)}&nbsp;</h2>
	<span class="spacer"></span>
{assign var="data_types" value=$principal->getDataTypes()}
{section name=data_type_index loop=$data_types}
  {assign var="cur_data_type" value=$data_types[data_type_index]}
  {if $cur_data_type != DATATYPE_IGNORE}
    {assign var="value_names" value=$principal->getValueNames($cur_data_type)}
    {section name=value_name_index loop=$value_names}
      {assign var="cur_value_name" value=$value_names[value_name_index]}
      {* show password later *}
      {if $cur_value_name != "password"}
  <span class="dottedSeparator"></span>
  <span class="left" title="{$principal->getValueDescription($cur_value_name, $cur_data_type)}">{$principal->getValueDisplayName($cur_value_name, $cur_data_type)}</span>
	<span class="right">{$nodeUtil->getInputControl($principal, $cur_value_name, $cur_data_type)}</span>
      {/if}
    {/section}
  {/if}
{/section}
	<span class="spacer"></span>
  {* roles *}
	<span class="spacer"></span>
	<h2>{translate text="Members"}</h2>
  <span class="dottedSeparator"></span>
  <span class="all">{$formUtil->getInputControl("principals", "checkbox[class=\"check\"]#fix:$principalBaseList", $principalList, true)}</span>
	<span class="spacer"></span>
</div>

</div>

{include file="lib:application/views/include/footer.tpl"}
