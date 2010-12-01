{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
<script>
  function init()
  {ldelim}
    Ext.QuickTips.init();

    // result grid
    var grid = new wcmf.grid.Grid();
    var columDefs = [];
    {math equation="571/x" x=2 assign="columnWidth" format="%d"}
    columDefs.push({ldelim}id:"type", dataIndex:"type", header:"{translate text="Type"}", width:{$columnWidth}, sortable:true{rdelim});
    columDefs.push({ldelim}id:"summary", dataIndex:"summary", header:"{translate text="Summary"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});

    var buttonDefs = [];

    grid.init('{translate text="Search results for: %1%" r0=$searchterm}', '{$type}', '{$obfuscator->obfuscate($searchdef)}', columDefs, 
      {ldelim}paging:false, autoheight:true, singleSelect:true, ddRows:false, groupBy:'type'{rdelim}, 
      [new wcmf.grid.EditAction({ldelim}type:'user'{rdelim}), new wcmf.grid.DeleteAction()], buttonDefs,
      {ldelim}completeObjects:true, renderValues:false{rdelim}
    );
    grid.getGridImpl().applyToMarkup('resultGrid');
    grid.load();

  {rdelim}
</script>
</head>
<body onload="init();">
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div id="tabnav">
{include file="lib:application/views/include/root_type_tabs.tpl"}
</div>

{include file="lib:application/views/include/navigation.tpl"}
{include file="lib:application/views/include/error.tpl"}

<div class="contentblock resultpanel">
  <div id="resultGrid" style="border:1px solid #99bbe8;overflow: hidden; width: 665px;"></div>
</div>

{include file="lib:application/views/include/footer.tpl"}
