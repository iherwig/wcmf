{extends file="lib:application/views/base.tpl"}

{block name=navigation}
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <div class="nav-collapse">
        <ul class="nav">
          <li class="active"><a href="#">{translate text="Home"}</a></li>
          <li><a href="#" onclick="wcmf.Action.browseResources();">{translate text="Media Pool"}</a></li>
          <form class="navbar-search pull-left">
            <input type="text" class="search-query" placeholder="Search">
          </form>
        </ul>
        <ul class="nav pull-right">
          <li><a href="#">{translate text="Administration"}</a></li>
          <li><a href="#" onclick="wcmf.Action.logout();">{translate text="Logout"}</a></li>
        </ul>
     </div>
{/block}

{*block name=navigation}
    <div data-dojo-type="dijit.MenuBar" id="navMenu">
      <div data-dojo-type="dijit.MenuBarItem"
        onClick="newWindowEx('DisplayController', '', 'treeview', 'treeviewWindow', 'width=800,height=700,resizable=no,scrollbars=no,locationbar=no', '&sid={$sid}');">
        <span>{translate text="Content Tree"}</span>
      </div>
      <div data-dojo-type="dijit.MenuBarItem" data-dojo-props="onClick:function() {
        wcmf.Action.browseResources();
      }">
        <span>{translate text="Media Pool"}</span>
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
{/block*}
