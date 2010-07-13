{assign var="attributes" value=$attributes|default:'class="default"'}
{if !$resourceBrowserCodeAdded}
<script type="text/javascript">
{literal}
function SetUrl(value, fieldName)
{
  setVariable(fieldName, value);
}
{/literal}
</script>
{/if}
{if $enabled}
{if !$attributeList.type}
  {assign var="type" value="link"}
{else}
  {assign var="type" value=$attributeList.type}
{/if}
{if !$attributeList.subtype}
  {assign var="subtype" value="resource"}
{else}
  {assign var="subtype" value=$attributeList.subtype}
{/if}
<input type="text" name="{$name}" value="{$value}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty(this.name);" /><br/>
<a href="javascript:newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type={$type}&subtype={$subtype}&fieldName={$name}&directory={$directory}')">{translate text="Browse Server"}</a>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
