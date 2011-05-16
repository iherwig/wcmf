<input
  id="{$name}"
  {$attributes}
  data-dojo-type="dijit.form.ValidationTextBox"
  data-dojo-props='
    type:"password",
    name:"{$name}",
    value:"{$value}"
    {if !$enabled}
      , disabled:true
    {/if}
    {$validationString}
  '
/>
