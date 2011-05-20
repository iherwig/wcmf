{if $enabled}
  {if $controlIndex == 0}
    {configvalue section="cms" key="libDir" varname="libDir"}
  <script type="text/javascript">

    var CKEDITOR_BASEPATH = '{$libDir}3rdparty/ckeditor/';
  </script>
  <script type="text/javascript" src="{$libDir}3rdparty/ckeditor/ckeditor.js"></script>
  {/if}

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
    data-dojo-type="wcmf.ui.CkEditorWidget"
    data-dojo-props='
      name:"{$name}"
      {if !$enabled}
        , disabled:true
      {/if}
      {foreach key=listkey item=listvalue from=$attributeList}
        , {$listkey}:"{$listvalue}"
      {/foreach}
      , filebrowserBrowseUrl: "main.php?action=browseResources&sid={sessionid}"
      , customConfig: "../../../application/script/ckconfig.js"
      , stylesSet: "wcmf:../../../application/script/ckstyles.js"
      {$validationString}
    '
  >
  {$value}
  </textarea>
{else}
  <span class="disabled">{$value|strip_tags}</span>
{/if}
