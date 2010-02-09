{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body>
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div class="error">{translate text="This page is currently not available"}.<br />
	{translate text="Reason"}: {$errorMsg}<br />
</div>
<br /><br />
<script language="JavaScript">
  if (window.opener == null)
	  document.write('{translate text="Click %1%here%2% to return to application" r1="<a href=\"javascript:submitAction(\'login\');\" class=\"cms\">" r2="</a>"}');
</script>

{include file="lib:application/views/include/footer.tpl"}
