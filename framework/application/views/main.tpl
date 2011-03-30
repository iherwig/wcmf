{extends file="lib:application/views/base.tpl"}

{block name=navigation}
    <div data-dojo-type="dijit.MenuBar" id="navMenu">
      <div data-dojo-type="dijit.MenuBarItem"
        onClick="newWindowEx('DisplayController', '', 'treeview', 'treeviewWindow', 'width=800,height=700,resizable=no,scrollbars=no,locationbar=no', '&sid={$sid}');">
        <span>{translate text="Content Tree"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem"
        onClick="newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type=link&subtype=resource');">
        <span>{translate text="Media Pool"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem"
        onClick="newWindowEx('', '', 'export', 'exportWindow', 'width=360,height=120,scrollbars=no,resizable=yes,locationbar=no', '&sid={$sid}');">
        <span>{translate text="Export"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem" onClick="submitAction('search');">
        <span>{translate text="Search"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem" onClick="submitAction('edituser');">
        <span>{translate text="User data"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem" onClick="setContext('admin'); submitAction('administration');">
        <span>{translate text="Administration"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem" data-dojo-props="onClick:function() {
        wcmf.Action.logout();
      }">
        <span>{translate text="Logout"}</span>
      </div>
    </div>
{/block}
