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
<a href="javascript:newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type=link&subtype=content&fieldName={$name}')">{translate text="Internal Link"}</a>
{if $value}| <a href="{$value}"{if $isExternal} target="_blank"{/if}>{translate text="Test"}</a>{/if}