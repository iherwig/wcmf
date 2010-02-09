<div id="navmeta">
  <ul>
{if $_controller == "LoginController"}
    <li><a href="javascript:submitAction('dologin');" id="navlogin">{translate text="Log in"}</a></li>
{else}
  {if $_controller == "AdminController"}
    <li><a href="javascript:newWindowEx('', '', 'indexAll', 'indexWindow', 'width=360,height=120,scrollbars=no,resizable=yes,locationbar=no', '&sid={sessionid}')" id="navinadexall">{translate text="Create Search Index"}</a></li>
    <li><a href="javascript:setContext('cms'); doDisplay(''); submitAction('cms');" target="_top" id="navcms">{translate text="CMS"}</a></li>
  {/if}
  {if $_controller == "TreeViewController"}
    <li><a href="blank.html" id="navclose">{translate text="Close"}</a></li>
  {/if}
  {if $authUser != null && $_controller == "DisplayController"}
    <li><a href="javascript:submitAction('edituser');" id="navuserdata">{translate text="User data"}</a></li>
  {/if}
  {if $authUser != null}
    <li><a href="main.php" target="_top" id="navlogout">{translate text="Logout"}</a></li>
  {/if}
{/if}
  </ul>
</div>

<div id="navcontent">
  <ul>
{if $_controller != "LoginController"}
  {if $_context == "user" || $_context == "role" || $_context == "config" || $_controller == "EditRightsController"}
    <li><a href="javascript:setContext('admin'); submitAction('overview');" id="navback">{translate text="Back"}</a></li>
  {/if}
  {if $_controller == "UserController"}
    <li><a href="javascript:submitAction('ok');" id="navback">{translate text="Back"}</a></li>
  {else}
    <li><a href="javascript:doDisplay('{$oid}'); submitAction('');" id="navreload">{translate text="Reload"}</a></li>
  {/if}
  {if $_context == "user" || $_context == "role" || $_context == "config" || $_controller == "EditRightsController" || $_controller == "UserController"}
    <li><a href="javascript:doSave(); submitAction('save');" id="navsave">{translate text="Save"}</a></li>
  {/if}
    <li><a href="javascript:newWindow('DisplayController', 'admin', 'treeview', 'treeviewWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,locationbar=no')" id="navcontenttree">{translate text="Content Tree"}</a></li>
{/if}
  </ul>
</div>

<span class="separator"></span>
 