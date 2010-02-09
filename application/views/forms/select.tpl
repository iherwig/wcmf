{assign var="attributes" value=$attributes|default:'class="default" size="1"'}
{if $enabled}
  {if !$isAsync}
<select name="{$name}" {$attributes} {if $error != ''}style="border-color:#ff2b40"{/if} onchange="setDirty();" >
    {assign var="selected" value=0}
    {assign var="optionsstring" value=""}
    {foreach key=listkey item=listvalue from=$listMap}
      {if ((!is_array($value) && strval($listkey) == strval($value)) || (is_array($value) && in_array($listkey, $value)))}
      {assign var="selected" value=1}
        {assign var="optionsstring" value="$optionsstring<option value=\"$listkey\" selected=\"selected\">$listvalue</option>"}
      {else}
        {assign var="optionsstring" value="$optionsstring<option value=\"$listkey\">$listvalue</option>"}
      {/if}
    {/foreach}
    {if $selected == 0}
      {$optionsstring|replace:"<option value=\"\">":"<option value=\"\" selected=\"selected\">"}
    {else}
      {$optionsstring}
    {/if}
</select>
  {else}
    {uniqueid varname="layerId"}
<input type="text" id="{$layerId}" {$attributes} onchange="setDirty();" />
<script type="text/javascript">
  new Listbox().init("{$layerId}", "{$name}", "{$entityType}", "{$value}", "{$translatedValue}", "{$obfuscator->obfuscate($filter)}", null, null);
</script>
  {/if}
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
