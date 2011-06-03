{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
<script language="JavaScript">
{literal}
  function init()
  {
    Ext.QuickTips.init();
    
    var tree = new wcmf.tree.Tree({
      renderTo:'tree-div'
    });
    tree.render();
  }
  
  function setUrl(url)
  {
    // the ' was replaced by \' before passing, so replace back
    url = url.replace(/\\'/g, "'");
    
    // we assume that this page is opened in an iframe
    // (so the opener window is parent.window.opener)
    parent.window.opener.SetUrl(url, '{/literal}{$fieldName}{literal}');
    parent.window.close();
  }
{/literal}
</script>
</head>
<body onload="init();">

{include file="lib:application/views/include/formheader.tpl"}

<input type="hidden" name="fieldName" value="{$fieldName}" />
<div id="tree-div" style="overflow:auto; height:500px;width:650px"></div>

</form>

</body>
</html>
