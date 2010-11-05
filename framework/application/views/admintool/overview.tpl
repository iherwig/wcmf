{include file="lib:application/views/include/docheader.tpl"}
<head>
{include file="lib:application/views/include/header.tpl"}
<script>
{literal}
  /**
   * @class wcmf.grid.EditPrincipalAction. Action for editing principal items
   */
  wcmf.grid.EditPrincipalAction = function(config) {
      Ext.apply(this, config);
      wcmf.grid.EditPrincipalAction.superclass.constructor.call(this);
  };
  Ext.extend(wcmf.grid.EditPrincipalAction, wcmf.grid.EditAction, {

      performAction: function(actionName, record) {
        setContext(this.type); doDisplay(record['id']); submitAction('editprincipal');
      }
  });
  /**
   * @class wcmf.grid.DeletePrincipalAction. Action for deleting principal items
   */
  wcmf.grid.DeletePrincipalAction = function(config) {
      Ext.apply(this, config);
      wcmf.grid.DeletePrincipalAction.superclass.constructor.call(this);
  };
  Ext.extend(wcmf.grid.DeletePrincipalAction, wcmf.grid.DeleteAction, {

      getSupportedActions: function() {
        return ['delete'];
      },

      performAction: function(actionName, record) {
        var _this = this;
        var _grid = this.grid;
        Ext.MessageBox.confirm(Message.get("Delete %1%", [record['id']]), Message.get("Really delete node %1%?", [record['id']]), 
          function(btn) {
            if (btn == "yes") {
              Action.perform('delprincipal', {deleteoids:record['id']}, _grid.actionPerformed, _this);
            }
          });
      }
  });
{/literal}

  function init()
  {ldelim}
    Ext.QuickTips.init();

    // user grid
    var grid = new wcmf.grid.Grid();
    var columDefs = [];
    {count_items varname="numColumns" array=$userTemplateNode->getDisplayValues(true)}
    {math equation="361/x" x=$numColumns assign="columnWidth" format="%d"}
    {foreach key=name item=value from=$userTemplateNode->getDisplayValues(true)}
    columDefs.push({ldelim}id:"{$name|default:"-"}", dataIndex:"{$name}", header:"{$name|default:"-"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});
    {/foreach}

    var buttonDefs = [];
    buttonDefs.push({ldelim}icon:'images/new.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Create new User"}'{rdelim}, handler:function(){ldelim}setContext('user'); doNew('user'); submitAction('newprincipal');{rdelim}{rdelim});

    grid.init('{translate text="Users"}', '{$userType}', '{$obfuscator->obfuscate($nodeUtil->getNodeQuery($userType))}', columDefs, {ldelim}paging:true, autoheight:true, singleSelect:true, ddRows:false{rdelim}, [new wcmf.grid.EditPrincipalAction({ldelim}type:'user'{rdelim}), new wcmf.grid.DeletePrincipalAction()], buttonDefs);
    grid.getGridImpl().applyToMarkup('userGrid');
    grid.load();

    // role grid
    var grid = new wcmf.grid.Grid();
    var columDefs = [];
    {count_items varname="numColumns" array=$roleTemplateNode->getDisplayValues(true)}
    {math equation="361/x" x=$numColumns assign="columnWidth" format="%d"}
    {foreach key=name item=value from=$roleTemplateNode->getDisplayValues(true)}
    columDefs.push({ldelim}id:"{$name|default:"-"}", dataIndex:"{$name}", header:"{$name|default:"-"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});
    {/foreach}

    var buttonDefs = [];
    buttonDefs.push({ldelim}icon:'images/new.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Create new Role"}'{rdelim}, handler:function(){ldelim}setContext('role'); doNew('role'); submitAction('newprincipal');{rdelim}{rdelim});

    grid.init('{translate text="Roles"}', '{$roleType}', '{$obfuscator->obfuscate($nodeUtil->getNodeQuery($roleType))}', columDefs, {ldelim}paging:true, autoheight:true, singleSelect:true, ddRows:false{rdelim}, [new wcmf.grid.EditPrincipalAction({ldelim}type:'role'{rdelim}), new wcmf.grid.DeletePrincipalAction()], buttonDefs);
    grid.getGridImpl().applyToMarkup('roleGrid');
    grid.load();
    
  {rdelim}
</script>
</head>
<body onload="init();">
<div id="page">
{include file="lib:application/views/include/formheader.tpl"}
{include file="lib:application/views/include/title.tpl"}

<div id="tabnav"></div>

{include file="lib:application/views/admintool/include/navigation.tpl"}
{include file="lib:application/views/include/error.tpl" displayMessageDialog="false"}

<div class="contentblock userspanel">
  <div id="userGrid" style="border:1px solid #99bbe8;overflow: hidden; width: 445px;"></div>
</div>

<div class="contentblock rolespanel">
  <div id="roleGrid" style="border:1px solid #99bbe8;overflow: hidden; width: 445px;"></div>
</div>

<div class="contentblock duplicatepanel">
	<span class="spacer"></span>
  <h2>{translate text="New instance"}</h2>
  <span class="left"><input type="text" name="newInstanceName" class="wide"></span>
  <span class="left"><a href="javascript:submitAction('newInstance');" class="cms">{translate text="Create new instance"}</a></span>
</div>

<div class="contentblock backuppanel">
	<span class="spacer"></span>
  <h2>{translate text="Backup"}</h2>
  {assign var="defaultBackupName" value=$smarty.now|date_format:"%Y-%m-%d"}
  <span class="left">{$formUtil->getInputControl("makeBackupName", 'text[class="wide"]', $defaultBackupName, true)}</span>
  <span class="left"><a href="javascript:newWindowEx('AdminController', '', 'makebackup', 'backupWindow', 'width=360,height=120,scrollbars=no,locationbar=no', '&backupName='+getVariable('makeBackupName')+'&paramsSection=database&sourceDir=include/');" class="cms">{translate text="Create Backup"}</a></span>
  <span class="left">{$formUtil->getInputControl("restoreBackupName", 'select[class="wide"]#fkt:g_getBackupNames', "", true)}</span>
  <span class="left"><a href="javascript:newWindowEx('AdminController', '', 'restorebackup', 'backupWindow', 'width=360,height=120,scrollbars=no,locationbar=no', '&backupName='+getVariable('restoreBackupName')+'&paramsSection=database&sourceDir=include/');" class="cms">{translate text="Restore Backup"}</a></span>  
</div>

<div class="contentblock configurationpanel">
	<span class="spacer"></span>
	<h2>{translate text="Configuration Files"}</h2>
{section name=configfiles_index loop=$configfiles}
{assign var="file" value=$configfiles[configfiles_index]}
{cycle name=configfiles_cycle values=light,dark assign=style}
  <div class="row{$style}">
  	<span class="left"><a href="javascript:setContext('config'); doDisplay('{$file}'); submitAction('editconfig');" class="{$style}" title="{$file}">{$file|truncate:18:"...":true}</a></span>
  	<span class="right">
  	  <a href="javascript:setContext('config'); doDisplay('{$file}'); submitAction('editconfig');" class="{$style}" title="{translate text="Edit %1%" r1=$file}"><img src="images/edit.png" alt="{translate text="Edit %1%" r1=$file}" title="{translate text="Edit %1%" r1=$file}" border="0"></a>
  	  {if $file != $mainconfigfile}<a href="javascript:if(doDelete('{$file}', true, '{translate text="Really delete configuration %1%?" r1=$file}')) submitAction('delconfig');" class="{$style}"><img src="images/delete.png" alt="{translate text="Delete %1%" r1=$file}" title="{translate text="Delete %1%" r1=$file}" border="0"></a>{else}<a href="#">&nbsp;</a>{/if}</span>
  </div>
{/section}
	<span class="spacer"></span>
	<span class="left"><a href="javascript:setContext('config'); doNew('config'); submitAction('newconfig');"><img src="images/new.png" alt="{translate text="Create new Configuration File"}" title="{translate text="Create new Configuration File"}" border="0"></a></span>
</div>

{include file="lib:application/views/include/footer.tpl"}
