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
<input type="text" name="{$name}" value="{$value}" {$attributes} onchange="setDirty();" /><br />
<a href="javascript:newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type=link&subtype=content&fieldName={$name}')">{translate text="Internal Link"}</a>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
{if $value}| <a href="{$value}"{if $isExternal} target="_blank"{/if}>{translate text="Test"}</a>{/if}