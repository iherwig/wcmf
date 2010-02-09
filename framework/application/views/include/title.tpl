{* uses the following variables: hideTitle ("true"|"false") *}
<a name="top">&nbsp;</a>

{if $hideTitle == "false" || !$hideTitle}
<div id="head">
  <span><a href="http://wcmf.sourceforge.net" target="_blank"><img src="images/wcmf_logo.gif" width="180" height="54" alt="wcmf logo" border="0" /></a></span>
  <span id="title">{translate text=$applicationTitle}</span>
  <span id="logininfo">{if $authUser != null && $_controller != "TreeViewController"}{translate text="Logged in as %1% since %2%" r0=$authUser->getLogin() r1=$authUser->getLoginTime()}{/if}</span>
</div>
{/if}
