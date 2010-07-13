{assign var="attributes" value=$attributes|default:'class="check"'}
{if $enabled}
<input type="hidden" name="{$name}" value="{$value}" onchange="setDirty(this.name);" />
<input type="checkbox" {if $value}checked="checked"{/if} {$attributes} onchange="setValue('{$name}', this.checked)" />
<script type='text/javascript'>
{literal}
  function setValue(variable, checked) {
    var value=0;
    if (checked) {
      value=1;
    }
    setVariable(variable, value);
  }
{/literal}
</script>  
{else}
<span class="disabled" {$attributes}>{$value}</span>
{/if}
