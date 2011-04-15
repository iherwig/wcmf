{$validationString=''}
{if $attributeDescription}
  {$regExp=$attributeDescription->getRestrictionsMatch()}
  {if $regExp}
    {$invalidMessage=$attributeDescription->getRestrictionsDescription()}
    {$validationString="regExp='$regExp' invalidMessage='$invalidMessage'"}
  {/if}
{/if}
<input type="text" id="{$name}" name="{$name}" value="{$value}" {if !$enabled}disabled="true"{/if} {$attributes} {$validationString} dojoType="dijit.form.ValidationTextBox"/>
