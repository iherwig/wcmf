<div class="controlContainer">
{foreach key=listkey item=listvalue from=$listMap}
  <input
    id="{$name}_{$listkey}"
    {$attributes}
    data-dojo-type="dijit.form.RadioButton"
    data-dojo-props='
      type:"radio",
      name:"{$name}",
      value:"{$listkey}"
      {if ((!is_array($value) && strval($listkey) == strval($value)) || (is_array($value) && in_array($listkey, $value)))}
        , checked:"checked"
      {/if}
      {if !$enabled}
        , disabled:true
      {/if}
      {* TODO: onChange: function(){ldelim}console.log("changed"){rdelim} *}
    '
  />
  <label for="{$name}_{$listkey}">{$listvalue}</label><br />
{/foreach}
</div>