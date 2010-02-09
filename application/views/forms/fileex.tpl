{assign var="attributes" value=$attributes|default:'class="default"'}
{if $enabled}
<input type="hidden" name="MAX_FILE_SIZE" value="{$maxFileSize}" /><input type="file" name="{$name}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty();" /><br />
{if $value != ''}
<input type="checkbox" name="delete{$fieldDelimiter}{$name}" class="check" />{translate text="delete file"} | 
<a href="{$uploadDir}/{$value}" target="_blank" title="{$value}">{translate text="preview file"}</a>
{/if}
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
