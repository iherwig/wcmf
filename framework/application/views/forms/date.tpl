{assign var="attributes" value=$attributes|default:'class="default"'}
{if $enabled}
    {uniqueid varname="layerId"}
<input type="text" value="{$value}" name="{$name}" id="{$layerId}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty();" />
<script type='text/javascript'>
  var date = new Ext.form.DateField({literal}{{/literal}allowBlank:true,format:'{translate text="m/d/Y"}',applyTo:'{$layerId}'{literal}}{/literal});
</script>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}