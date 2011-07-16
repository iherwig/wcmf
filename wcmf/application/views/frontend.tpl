{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript" src="main.php?action=model"></script>
{/block}

{block name=left}
{*
<div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'leading', splitter:true, _splitterClass:dojox.layout.ToggleSplitter">
  <!--div data-dojo-type="wcmf.ui.ObjectTree" id="objectTree" minSize="20" style="width:300px;"></div-->
</div>
*}
{/block}

{block name=center}
<div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'center'">
  <div data-dojo-type="wcmf.ui.TypeTabContainer" id="typeTabContainer" style="width:50%; height:100%"></div>
</div>
{/block}