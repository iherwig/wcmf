{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body>
<div id="page">

{include file="lib:application/views/include/formheader.tpl"}

<input type="hidden" name="type" value="{$type}" />
<input type="hidden" name="subtype" value="{$subtype}" />
<input type="hidden" name="fieldName" value="{$fieldName}" />
<input type="hidden" name="directory" value="{$directory}" />
<input type="hidden" name="uploadDir" value="{$directory}" />

<div id="tabnav">
<ul>
{if $type == 'link'}
  <li{if $subtype == "content"} class="current"{/if} id="navresourceviewcontent"><a href="javascript:setVariable('subtype', 'content'); submitAction('');">{translate text="Content"}</a></li>
  <li{if $subtype == "resource"} class="current"{/if} id="navresourceviewresource"><a href="javascript:setVariable('subtype', 'resource'); submitAction('');">{translate text="Resources"}</a></li>
{/if}
{if $type == 'image'}
  <li{if $subtype == "resource"} class="current"{/if} id="navresourceviewresource"><a href="javascript:setVariable('subtype', 'resource'); submitAction('');">{translate text="Resources"}</a></li>
{/if}
</ul>
</div>

{if $subtype == 'content'}
  {include file="lib:application/views/include/resourcelist_content.tpl"}
{/if}
{if $subtype == 'resource'}
  {include file="lib:application/views/include/resourcelist_resource.tpl"}
{/if}

</form>
</div>

</body>
</html>