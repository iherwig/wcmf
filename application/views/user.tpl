{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body>
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div id="tabnav"></div>

{include file="lib:application/views/include/navigation.tpl" hideTitle="false"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<div id="leftcol">

{*------ Edit ------*}
<div class="contentblock">
	<h2">{translate text="User"}</h2>
	<span class="spacer"></span>
	{* password *}
  <input type="hidden" name="changepassword" value="yes" />
  <span class="dottedSeparator"></span>
  <span class="left">{translate text="Old Password"}</span>
	<span class="right">{$formUtil->getInputControl("oldpassword", "password", "", true)}</span>
  <span class="dottedSeparator"></span>
  <span class="left">{translate text="New Password"}</span>
	<span class="right">{$formUtil->getInputControl("newpassword1", "password", "", true)}</span>
  <span class="dottedSeparator"></span>
  <span class="left">{translate text="New Password Repeated"}</span>
	<span class="right">{$formUtil->getInputControl("newpassword2", "password", "", true)}</span>
	<span class="spacer"></span>
  <span class="all">{$message}</span>
</div>

</div>

{include file="lib:application/views/include/footer.tpl"}
