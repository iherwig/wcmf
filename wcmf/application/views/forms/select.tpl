<script type="text/javascript">
var myData = {
  identifier:"value",
  label:"name",
  items:[
  {foreach key=listkey item=listvalue from=$listMap}
    {ldelim}name:"{$listvalue}", value:"{$listkey}"{rdelim},
  {/foreach}
  ]
}
</script>
<div dojoType="dojo.data.ItemFileReadStore" data="myData" jsId="myStore" class="hidden"></div>

<input
  {$attributes}
  dojoType="dijit.form.FilteringSelect"
  placeHolder="{$translatedValue}"
  store="myStore"
  searchAttr="name"
  name="{$name}"
  {if !$enabled}
    disabled
  {/if}
/>
