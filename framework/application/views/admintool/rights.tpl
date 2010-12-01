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

<div class="contentblock">
 	<span class="spacer"></span>
  <h2>{translate text="Rights on %1%" r1="$oid"}</h2>
{section name=rightsindex loop=$rightnames}
{cycle name=$type values="light,dark" assign="style"}
 	<span class="spacer"></span>
  <div class="row{$style}">
{assign var="rightname" value=$rightnames[rightsindex]}
    <h2>{$rightname}</h2>
  	<span class="left">{translate text="Allow"}</span>
  	<span class="right">{translate text="Deny"}</span>
{section name=configindex loop=$configfiles}
{assign var="configname" value=$configfiles[configindex]}
  	<span class="all">{translate text="File"}: {$configname}</span>
{assign var="controlname" value=$rightname|cat:"_allow_"|cat:$configname|regex_replace:"/[ \.]/":""}
    <span class="left">{$formUtil->getInputControl("$controlname", "select[class='multiselect' multiple]#fix:$allroles", $rights.$configname.$rightname.allow, true)}<br />
      <a href="#" onclick="javascript:document.forms[0].elements['{$controlname}[]'].selectedIndex=-1">remove all</a></span>
{assign var="controlname" value=$rightname|cat:"_deny_"|cat:$configname|regex_replace:"/[ \.]/":""}
    <span class="right">{$formUtil->getInputControl("$controlname", "select[class='multiselect' multiple]#fix:$allroles", $rights.$configname.$rightname.deny, true)}<br />
      <a href="#" onclick="javascript:document.forms[0].elements['{$controlname}[]'].selectedIndex=-1">remove all</a></span>
{/section}
   	<span class="spacer"></span>
  </div>
{/section}
</div>

{include file="lib:application/views/include/footer.tpl"}
