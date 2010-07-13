{if $enabled}
{if !$FCKeditorCodeAdded}
<script type="text/javascript" src="{$libDir}fckeditor.js"></script>
<script type="text/javascript">
{literal}
  function fckCheck(editorInstance)
  {
    if (editorInstance.IsDirty() ) {
      setDirty(this.name);
    }
  }
  function FCKeditor_OnComplete(editorInstance)
  {
    editorInstance.Events.AttachEvent( 'OnSelectionChange', fckCheck ) ;
  }
{/literal}
</script>
{/if}
<script type="text/javascript">
  var oFCKeditor = new FCKeditor('{$name}', '350', '200');
  oFCKeditor.BasePath = '{$libDir}';
  
  // set custom configuration
  oFCKeditor.Config['BaseHref'] = '{$appDir}';
  oFCKeditor.Config['CustomConfigurationsPath'] = '{$appDir}script/fckconfig.js';
  oFCKeditor.Config['StylesXmlPath'] = '{$appDir}script/fckstyles.xml';
  oFCKeditor.Config['LinkBrowserURL'] = '{$appDir}main.php?usr_action=browseresources&type=link&subtype=content&sid={$sid}';
  oFCKeditor.Config['ImageBrowserURL'] = '{$appDir}main.php?usr_action=browseresources&type=image&subtype=resource&sid={$sid}';
  oFCKeditor.Config['FlashBrowserURL'] = '{$appDir}main.php?usr_action=browseresources&type=image&subtype=resource&sid={$sid}';
  
  // add additional attributes
{foreach key=listkey item=listvalue from=$attributeList}
  oFCKeditor.{$listkey} = {$listvalue};
{/foreach}

  oFCKeditor.Value = '{$value|escape:"quotes"}';
  oFCKeditor.Create();
</script>
{else}
<span class="disabled" {$attributes}>{$value|strip_tags}</span>
{/if}
