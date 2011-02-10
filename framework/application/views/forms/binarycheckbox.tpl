{assign var="attributes" value=$attributes|default:'class="check"'}
{if $enabled}
<input type="hidden" name="{$name}" value="{$value}" onchange="setDirty(this.name);" />
<input type="checkbox" {if $value}checked="checked"{/if} {$attributes} onchange="this.checked ? setVariable('{$name}', 1) : setVariable('{$name}', 0)" />
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
