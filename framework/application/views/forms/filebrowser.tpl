{$attributes=$attributes|default:'class="default"'}
{if $controlIndex == 0}
<script type="text/javascript">
function SetUrl(value, fieldName) {
  setVariable(fieldName, value);
}
</script>
{/if}
{if $enabled}
  {if !$attributeList.type}
    {$type="link"}
  {else}
    {$type=$attributeList.type}
  {/if}
  {if !$attributeList.subtype}
    {$subtype="resource"}
  {else}
    {$subtype=$attributeList.subtype}
  {/if}
<input type="text" name="{$name}" value="{$value}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty(this.name);" /><br/>
<a href="javascript:newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type={$type}&subtype={$subtype}&fieldName={$name}&directory={$directory}')">{translate text="Browse Server"}</a>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
