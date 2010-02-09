{assign var="attributes" value=$attributes|default:'class="default"'}
{if $enabled}
<input type="password" name="{$name}" value="{$value}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty();" />
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
