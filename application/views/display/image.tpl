{assign var="attributes" value=$attributes|default:'class="imgdefault"'}
{if $exists}
  {if $width > 0}
    {if $height > 60}
      {assign var="displayHeight" value=60}
      {assign var="displayWidth" value=$displayHeight*$width/$height}
    {else}
      {assign var="displayHeight" value=$height}
      {assign var="displayWidth" value=$width}
    {/if}
<a href="{$value}" target="_blank" title="{$value}"><img src="{$value}" {$attributes} width="{$displayWidth}" height="{$displayHeight}" border="0"></a><br /><br />
  {else}
<a href="{$value}" target="_blank" title="{$value}">{$filename}</a>
  {/if}
{else}
{translate text="file not found: %1%" r0="$value"}
{/if}
