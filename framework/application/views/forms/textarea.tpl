{$validationString=''}
{if $attributeDescription}
  {$regExp=$attributeDescription->getRestrictionsMatch()}
  {if $regExp}
    {$invalidMessage=$attributeDescription->getRestrictionsDescription()}
    {$validationString=", regExp:\"$regExp\", invalidMessage:\"$invalidMessage\""}
  {/if}
{/if}
<textarea
  id="{$name}"
  {$attributes}
  data-dojo-type="dijit.form.Textarea"
  data-dojo-props='
    name:"{$name}"
    {if !$enabled}
      , disabled:true
    {/if}
    {$validationString}
  '
>
{$value}
</textarea>
