{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
</head>
<body>
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}
{include file="lib:application/views/include/navigation.tpl"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<script language="Javascript" src="script/login.js"></script>

<div class="contentblock">
	<span class="spacer"></span>
	<span class="left">{translate text="Login"}</span>
  <span class="right">{$formUtil->getInputControl("login", "text", "", true)}</span>
  <span class="left">{translate text="Password"}</span>
  <span class="right">{$formUtil->getInputControl("password", "password", "", true)}</span>
	<span class="spacer"></span>
  <span class="left">{translate text="Remember me"}</span>
	<span class="right">{$formUtil->getInputControl("remember_me", "checkbox[class='check']#fix:1[ ]", "", true)}</span>
	<span class="spacer"></span>
  <span class="left">&nbsp;</span>
  <span class="right"><a href="javascript:submitAction('dologin');">{translate text="Log in"}</a></span>
</div>

{include file="lib:application/views/include/footer.tpl"}
