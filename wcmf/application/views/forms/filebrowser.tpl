{$validationString=''}
{if $attributeDescription}
  {$regExp=$attributeDescription->getRestrictionsMatch()}
  {if $regExp}
    {$invalidMessage=$attributeDescription->getRestrictionsDescription()}
    {$validationString=", regExp:\"$regExp\", invalidMessage:\"$invalidMessage\""}
  {/if}
{/if}
<input
  id="{$name}"
  {$attributes}
  data-dojo-type="dijit.form.ValidationTextBox"
  data-dojo-props='
    name:"{$name}",
    value:"{$value}"
    {if !$enabled}
      , disabled:true
    {/if}
    {$validationString}
  '
/>
<a href="#" onclick="wcmf.Action.browseResources('{$name}'); return false">{translate text="Browse"}</a>
