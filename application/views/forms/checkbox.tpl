{assign var="attributes" value=$attributes|default:'class="check"'}
{if $enabled}
{foreach key=listkey item=listvalue from=$listMap}
{if ((!is_array($value) && $listkey == $value) || (is_array($value) && in_array($listkey, $value)))}
  <input type="checkbox" name="{$name}[]" value="{$listkey}" checked {$attributes} onchange="setDirty();" /> {$listvalue}
{else}
  <input type="checkbox" name="{$name}[]" value="{$listkey}" {$attributes} onchange="setDirty();" /> {$listvalue}
{/if}
{/foreach}
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
