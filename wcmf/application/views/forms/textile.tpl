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
<script type="text/javascript">
  var secondDlg;
  dojo.addOnLoad(function()
  {
    // create the dialog:
    secondDlg = new dijit.Dialog({
      title: "Textile HTML Output Preview",
      style: "width: 500px"
    });
  });
  function showPreview()
  {
    new wcmf.persistence.Request().sendAjax({
      action: 'textilePreview',
      responseFormat: 'html',
      text: dijit.byId('{$name}').get('value')
    }).then(function(data) {
      // set the content of the dialog:
      secondDlg.set("content", data);
      secondDlg.show();
    });
  }
</script>
<button id="buttonTwo" dojoType="dijit.form.Button" onClick="showPreview();" type="button">Preview</button>


