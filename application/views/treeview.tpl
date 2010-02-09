{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
<script>
{literal}
  function init()
  {
    Ext.QuickTips.init();
    
    var tree = new wcmf.tree.Tree({
      renderTo:'tree-div'
    });
    tree.render();
  }
{/literal}
</script>
</head>
<body onload="init();">
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/navigation.tpl" hideTitle="true"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<div id="tree-div" style="overflow:auto; height:600px; width:630px"></div>

{include file="lib:application/views/include/footer.tpl"}
