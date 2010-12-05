{extends file="lib:application/views/base.tpl"}

{block name=title}
<a name="top">&nbsp;</a>
<div id="head">
  <span><a href="http://wcmf.sourceforge.net" target="_blank"><img src="images/wcmf_logo.gif" width="180" height="54" alt="wcmf logo" border="0" /></a></span>
  <span id="title">{configvalue key="applicationTitle" section="cms"}</span>
  <span id="logininfo">{if $authUser != null}{translate text="Logged in as %1% since %2%" r0=$authUser->getLogin() r1=$authUser->getLoginTime()}{/if}</span>
</div>
{/block}