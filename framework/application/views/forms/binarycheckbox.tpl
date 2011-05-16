<input
  id="{$name}"
  {$attributes}
  data-dojo-type="dijit.form.TextBox"
  data-dojo-props='
    type:"hidden",
    name:"{$name}"
  '
/>
<button
  {$attributes}
  dojoType="dijit.form.ToggleButton"
  {if $value}
    checked
  {/if}
  {if !$enabled}
    disabled
  {/if}
  onChange="console.log(dijit.byId('{$name}')); this.checked ? dijit.byId('{$name}').set('value', 1) : dijit.byId('{$name}').set('value', 0);"
  iconClass="dijitCheckBoxIcon"
>
{* label text goes here *}
</button>
