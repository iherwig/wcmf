{assign var="attributes" value=$attributes|default:'class="default"'}
{if $enabled}
<input type="hidden" name="MAX_FILE_SIZE" value="{$maxFileSize}" /><input type="file" name="{$name}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty();" />
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
