{extends file="lib:application/views/base.tpl"}

{block name=navigation}
    <div dojoType="dijit.MenuBar" id="navMenu">
      <div dojoType="dijit.MenuBarItem" 
        onClick="doSave(); submitAction('save');">
        <span>{translate text="Save"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" 
        onClick="newWindowEx('DisplayController', '', 'treeview', 'treeviewWindow', 'width=800,height=700,resizable=no,scrollbars=no,locationbar=no', '&sid={$sid}');">
        <span>{translate text="Content Tree"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" 
        onClick="newWindowEx('', '', 'browseresources', 'browseWindow', 'width=800,height=700,resizable=yes,scrollbars=yes,status=yes,locationbar=no', '&type=link&subtype=resource');">
        <span>{translate text="Media Pool"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" 
        onClick="newWindowEx('', '', 'export', 'exportWindow', 'width=360,height=120,scrollbars=no,resizable=yes,locationbar=no', '&sid={$sid}');">
        <span>{translate text="Export"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" onClick="submitAction('search');">
        <span>{translate text="Search"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" onClick="newWindowEx('{$controller}', '', 'definesearch', 'definesearchWindow', 'width=600,height=600,scrollbars=yes,locationbar=no,resizable=yes', '&sid={$sid}');">
        <span>{translate text="Advanced Search"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" onClick="setContext('admin'); submitAction('administration');">
        <span>{translate text="Administration"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" onClick="submitAction('edituser');">
        <span>{translate text="User data"}</span>
      </div>
      <div dojoType="dijit.MenuBarItem" onClick="wcmf.Action.logout()">
        <span>{translate text="Logout"}</span>
      </div>
    </div>
{/block}
