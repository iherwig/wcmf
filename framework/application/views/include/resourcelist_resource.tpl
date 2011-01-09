<div id="popupcontent">
  <h2>{translate text="Content to link"}</h2>
	<span class="spacer"></span>
  <span>
    {input name="value-upload_file-file:0" type="file[style=\"width:250px; height:20px; line-height:20px; \"]" value="" editable=true}<br />
    {input name="value-1-upload_file-file:1" type="file[style=\"width:250px; height:20px; line-height:20px; \"]" value="" editable=true}<br />
    {input name="value-1-upload_file-file:2" type="file[style=\"width:250px; height:20px; line-height:20px; \"]" value="" editable=true}<br />
  </span>
  <span class="left"><a href="javascript:setContext('resourcelist'); submitAction('save');">{translate text="Upload selected file(s)"}</a></span>
  <span class="left note">{translate text="Please note: Special characters like %1% are not allowed in the filename." r1="*?[]$|><&;()/\#'`:\""}</span>
	<span class="spacer"></span>
  <h2>{translate text="Directories"}</h2>
  <span>{input name="newDir" type="text[style=\"width:245px\"]" value="" editable=true}<br />
    <a href="javascript:submitAction('createDir');">{translate text="Create directory"}</a></span>
	<span class="spacer"></span>
  <span>
{if $parentDirectory != $directory}
    <a href="javascript:setVariable('directory', '{$parentDirectory}'); submitAction('');">{translate text="Go up one folder"}</a> | 
{/if}
{if $baseDirectory != $directory}
    <a href="javascript:setVariable('directory', '{$baseDirectory}'); submitAction('');">{translate text="Go to top folder"}</a>
{/if}
	<br /><br />
{foreach item=dir from=$directories}
  {cycle values="light,dark" assign="style"}
    <div class="list row{$style}">
      <span class="left""><a href="#" onClick="javascript:setVariable('directory', '{$dir}'); submitAction('');">{$dir|replace:$directory:""}</a></span>
      <span class="right"><a href="#" onClick="javascript:if(doDelete('{$dir}', true, '{translate text="Really delete directory %1%?" r1=$dir|replace:$directory:""}')) submitAction('delete');" title="{translate text="Delete directory %1%" r1=$dir|replace:$directory:""}"><img src="images/delete.png" alt="{translate text="Delete %1%" r1=$dir|replace:$directory:""}" title="{translate text="Delete %1%" r1=$dir|replace:$directory:""}" border="0"></a></span>
    </div>
{/foreach}
  </span>
</div>

<div id="resourcecontent">
  <h2>{translate text="Files in %1%" r1=$linkedDirectoryPath|default:"/"}</h2>
{foreach key=url item=value from=$resourceList}
{if $url != ''}
	<span class="spacer"></span>
  <span>
  {if $value.maintype == 'image'}
    <a href="#" onClick="if (window.opener.SetUrl) window.opener.SetUrl('{$url}', '{$fieldName}'); window.close(); return true;">
    {if $value.height > 50}{image src=$url base="." height="50" params='border="0"'}
    {elseif $value.width > 400}{image src=$url base="." width="400" params='border="0"'}
    {else}{image src=$url base="." params='border="0"'}{/if}
    </a><br /><br />
  {/if}
    <a href="#" onClick="if (window.opener.SetUrl) window.opener.SetUrl('{$url}', '{$fieldName}'); window.close(); return true;">{$value.name}</a> ({$value.type}{if $value.width}, {$value.width}x{$value.height}{/if}{if $value.numReferences != -1}, {translate text="referenced %1% time(s)" r1=$value.numReferences}{/if})<br />
  <a href="#" onClick="window.open('{$url}');"><img src="images/preview.png" alt="{translate text="Preview"}" title="{translate text="Preview"}" border="0"></a>
  <a href="#" onClick="javascript:if(doDelete('{$url}', true, '{translate text="Really delete file %1%?" r1=$value.name}')) submitAction('delete');" title="{translate text="Delete file %1%" r1=$value.name}"><img src="images/delete.png" alt="{translate text="Delete %1%" r1=$value.name}" title="{translate text="Delete %1%" r1=$value.name}" border="0"></a></span>
{/if}
{/foreach}
</div>
{include file="lib:application/views/include/error.tpl"}
