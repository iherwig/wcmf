{assign var="attributes" value=$attributes|default:'class="radio"'}
{if $enabled}
{assign var="selected" value=0}
{assign var="controlstring" value=""}
{foreach key=listkey item=listvalue from=$listMap}
{if ((!is_array($value) && strval($listkey) == strval($value)) || (is_array($value) && in_array($listkey, $value)))}
{assign var="selected" value=1}
  {assign var="controlstring" value="$controlstring<input type=\"radio\" name=\"$name\" value=\"$listkey\" checked=\"checked\" $attributes onchange=\"setDirty(this.name);\" /> <label>$listvalue</label>"}
{else}
  {assign var="controlstring" value="$controlstring<input type=\"radio\" name=\"$name\" value=\"$listkey\" $attributes onchange=\"setDirty(this.name);\" /> <label>$listvalue</label>"}
{/if}
{/foreach}
{if $selected == 0}
  {$controlstring|replace:"value=\"\" >":"value=\"\" checked>"}
{else}
  {$controlstring}
{/if}
</select>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
