{if $enabled}
  <textarea class="ckeditor" cols="50" id="{$name}" name="{$name}" rows="10">{$value|escape:"quotes"}</textarea>

  {if $controlIndex == 0}
    {configvalue section="cms" key="libDir" varname="libDir"}
  <script type="text/javascript">
    var CKEDITOR_BASEPATH = '{$libDir}3rdparty/ckeditor/';
  </script>
  <script type="text/javascript" src="{$libDir}3rdparty/ckeditor/ckeditor.js"></script>
  {/if}

  <script type="text/javascript">
    function ckCheck()
    {
      if (this.checkDirty())
      {
        console.log("dirty");
      }
    }
    
    // delete any old instance of same id and create new
    if (CKEDITOR.instances["{$name}"])
    {
      delete CKEDITOR.instances["{$name}"];
    }
    var ckeditorInstance = CKEDITOR.replace("{$name}",
    {
      {foreach key=listkey item=listvalue from=$attributeList}
        {$listkey} : {$listvalue},
      {/foreach}
      customConfig : '../../../application/script/ckconfig.js'
    });

    // assign handlers to check for changes of editor's content
    ckeditorInstance.on('instanceReady', function(e)
    {
      var self = this;
      this.document.on("keyup", function() { ckCheck.call(self) });
      this.document.on("paste", function() { ckCheck.call(self) });
    });
  </script>
{else}
  <span class="disabled">{$value|strip_tags}</span>
{/if}
