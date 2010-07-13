{assign var="attributes" value=$attributes|default:'class="default"'}
{if $enabled}
    {uniqueid varname="layerId"}
<input type="text" value="{$value}" name="{$name}" id="{$layerId}" {$attributes} {if $error != ''}style="border:1px dotted #EC0000"{/if} onchange="setDirty(this.name);" />
<script type='text/javascript'>
  new Ext.form.DateField({ldelim}allowBlank:true,format:'{translate text="m/d/Y"}',applyTo:'{$layerId}'{rdelim});
</script>
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}