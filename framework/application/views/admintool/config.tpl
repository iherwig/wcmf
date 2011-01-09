{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
<script language="Javascript">
  function gotoURL(location)
  {literal}{
  	document.location.href = "#" + location;
  }
  {/literal}
</script>
</head>
<body onload="gotoURL('{$internallink}');">
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div id="tabnav"></div>

{include file="lib:application/views/admintool/include/navigation.tpl"}
{include file="lib:application/views/include/error.tpl"}

<div class="contentblock configurationpanel">
	<span class="spacer"></span>
	<h2>{translate text="Filename"}</h2>
{if $ismainconfigfile}
	<span class="right">{$configFilenameNoExtension}.ini</span>
{else}
  <span class="right">{input name="name" type="text" value=$configFilenameNoExtension editable=true}.ini</span>
{/if}
	<span class="spacer"></span>
</div>

<div class="contentblock configurationpanel">
	<h2>{translate text="Sections"}</h2>
{assign var="sections" value=$configfile->getSections()}
{section name=section_index loop=$sections}
{assign var="sectionname" value=$sections[section_index]}
{if !$configfile->isHidden($sectionname)}
{assign var="controlnamesection" value="type_section_section_"|cat:$sectionname}
{cycle name=section_cycle values=light,dark assign=style}
{if $configfile->isEditable($sectionname)}
  <div class="row{$style}">
  	<span class="left">{input name=$controlnamesection type="text[class=\"small$style\"]" value=$sectionname editable=true}</span>
  	<span class="right"><a href="#{$sectionname}" class="{$style}"><img src="images/edit.png" alt="{translate text="Edit %1%" r1=$sectionname}" title="{translate text="Edit %1%" r1=$sectionname}" border="0"></a> <a href="javascript:if(doDelete('{$controlnamesection}', true, '{translate text="Really delete section %1%?" r1=$sectionname}')) submitAction('delsection');" class="{$style}"><img src="images/delete.png" alt="{translate text="Delete section %1%" r1=$sectionname}" title="{translate text="Delete section %1%" r1=$sectionname}" border="0"></a></span>
  </div>
{else}
  <div class="row{$style}">
  	<span class="left">{$sectionname}</span>
  	<span class="right"><a href="#{$sectionname}" class="{$style}"><img src="images/preview.png" alt="{translate text="View %1%" r1=$sectionname}" title="{translate text="View %1%" r1=$sectionname}" border="0"></a></span>
  </div>
{/if}
{/if}
{/section}
	<span class="spacer"></span>
	<span class="left"><a href="javascript:doNew('section'); submitAction('newsection');" title="{translate text="Create new Section"}"><img src="images/new.png" alt="{translate text="Create new Section"}" border="0"></a></span>
</div>

{section name=section_index loop=$sections}
{assign var="sectionname" value=$sections[section_index]}
{if !$configfile->isHidden($sectionname)}
<div class="contentblock configurationpanel">
	<span class="spacer"></span>
	<h2><a name="{$sectionname}">[{$sectionname}]</a></h2>
  {assign var="sectionvalues" value=$configfile->getSection($sectionname)}
  {foreach key="option" item="value" from=$sectionvalues}
  {assign var="controlnameoption" value="type_option_section_"|cat:$sectionname|cat:"_option_"|cat:$option}
  {assign var="controlnamevalue" value="type_value_section_"|cat:$sectionname|cat:"_option_"|cat:$option}
		{if $configfile->isEditable($sectionname)}
			{cycle name=section_cycle values=light,dark assign=style}
  <div class="row{$style}">
  	<span class="left wide">control name=$controlnameoption type="text[class=\"small$style\"]" value=$option editable=true}<a name="{$sectionname|cat:"_"|cat:$option}">&nbsp;</a> {input name=$controlnamevalue type="text[class=\"small$style\"]" value=$value editable=true}</span>
  	<span class="right"><a href="javascript:if(doDelete('{$controlnameoption}', true, '{translate text="Really delete option %1%?" r1=$option}')) submitAction('deloption');" class="{$style}"><img src="images/delete.png" alt="{translate text="Delete option %1%" r1=$option"}" border="0"></a></span>
  </div>
		{else}
			{cycle name=section_cycle values=light,dark assign=style}
  <div class="row{$style}">
  	<span class="left wide" title="{$option} - {$value}">{$option}<a name="{$sectionname|cat:"_"|cat:$option}" class="{$style}">&nbsp;</a> {$value|truncate:25:"...":true}</span>
  	<span class="right"></span>
  </div>
		{/if}
	{/foreach}
	<span class="left"><a href="#"><img src="images/top.png" alt="{translate text="Goto Sections"}" border="0"></a> {if $configfile->isEditable($sectionname)}<a href="javascript:doSetParent('{$sectionname}'); doNew('option'); submitAction('newoption');" title="{translate text="Create Option in '%1%'" r1=$sectionname}"><img src="images/new.png" alt="{translate text="Create Option in '%1%'" r1=$sectionname}" border="0"></a>{/if} <a href="javascript:doSave(); submitAction('save');">{translate text="Save"}</a></span>
</div>
{/if}
{/section}

{include file="lib:application/views/include/footer.tpl"}
