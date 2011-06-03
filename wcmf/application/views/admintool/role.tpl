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
{include file="lib:application/views/include/error.tpl"}

<div id="leftcol">

{*------ Edit ------*}
<div class="contentblock">
  <h2 title="{translate text="object ID"}: {$oid|default:"-"}">{$principal->getDisplayValue(true)}&nbsp;</h2>
  <span class="spacer"></span>
{assign var="value_names" value=$principal->getValueNames()}
{section name=value_name_index loop=$value_names}
  {assign var="cur_value_name" value=$value_names[value_name_index]}
  {* show password later *}
  {if $cur_value_name != "password"}
  <span class="dottedSeparator"></span>
  <span class="left" title="{$principal->getValueDescription($cur_value_name, $cur_data_type)}">{$principal->getValueDisplayName($cur_value_name)}</span>
  <span class="right">{input node=$principal property=$cur_value_name}</span>
  {/if}
{/section}
  <span class="spacer"></span>
  {* roles *}
  <span class="spacer"></span>
  <h2>{translate text="Members"}</h2>
  <span class="dottedSeparator"></span>
  <span class="all">{input name="principals" type="checkbox[class=\"check\"]#fix:$principalBaseList" value=$principalList editalbe=true}</span>
  <span class="spacer"></span>
</div>

</div>

{include file="lib:application/views/include/footer.tpl"}
