<div id="navmeta">
<ul>
{if $_controller != "LoginController"}
	{if $_controller != "TreeViewController"}
	  {if $authUser->hasRole('administrators')}
		<li><a href="javascript:setContext('admin'); submitAction('administration');" target="_top" id="navadministration">{translate text="Administration"}</a></li>
    {/if}
		<li><a href="javascript:submitAction('edituser');" id="navuserdata">{translate text="User data"}</a></li>
		<li><a href="javascript:submitAction('logout');" target="_top" id="navlogout">{translate text="Logout"}</a></li>
	{/if}
{/if}
</ul>
</div>

<div id="navcontent">
  <ul>
{if $_controller != "LoginController"}
	{if $_controller == "UserController"}
  	<li><a href="javascript:submitAction('ok');" id="navback">{translate text="Back"}</a></li>
  	<li><a href="javascript:doSave(); submitAction('save');" id="navsave">{translate text="Save"}</a></li>
	{else}
  	<li><a href="javascript:doDisplay('{$oid}'); submitAction('');" id="navreload">{translate text="Reload"}</a></li>
		{if $_controller != "TreeViewController"}
  	<li><a href="javascript:doSave(); submitAction('save');" id="navsave">{translate text="Save"}</a></li>
  	<li><a href="javascript:newWindowEx('DisplayController', '', 'treeview', 'treeviewWindow', 'width=800,height=700,resizable=no,scrollbars=no,locationbar=no', '&sid={$sid}')" id="navcontenttree">{translate text="Content Tree"}</a></li>
    <li><a href="javascript:newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type=link&subtype=resource')">{translate text="Media Pool"}</a></li>
  		{if $authUser->hasRole('administrators')}
  	<li><a href="javascript:newWindowEx('', '', 'export', 'exportWindow', 'width=360,height=120,scrollbars=no,resizable=yes,locationbar=no', '&sid={$sid}')" id="navexport">{translate text="Export"}</a></li>
	  	{/if}
    <li><a href="javascript:submitAction('search');">{translate text="Search"}</a> {$formUtil->getInputControl("searchterm", "text[class='small']", $searchterm, true)}</li>
    <li><a href="javascript:newWindowEx('{$_controller}', '', 'definesearch', 'definesearchWindow', 'width=600,height=600,scrollbars=yes,locationbar=no,resizable=yes', '&sid={$sid}');">{translate text="Advanced Search"}</a></li>
	  {/if}
	{/if}
{/if}
  </ul>
</div>

<span class="separator"></span>
