{assign var="attributes" value=$attributes|default:'class="default" cols="50" rows="5"'}
{if $enabled}
<textarea name="{$name}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty();" >{$value}</textarea>
{else}
<span class="disabled" {$attributes}>{$value|strip_tags}</span>
{/if}
