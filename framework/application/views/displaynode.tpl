{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript">
$(document).ready(function() {
    Ext.QuickTips.init();
{if $viewMode == 'detail'}
    var grids = [];
    var columDefs = [];
    var buttonDefs = [];
    
  // parents
  {foreach item=template from=$possibleparents}
    {$role=$template->getRole($node->getOID())}
    {$parent=$template->getProperty('assignedParent')}
    {if $parent}
      {$parentid=$parent->getDBID()}
    {else}
      {$parentid=''}
    {/if}
    {if $template->hasValue('sortkey')}{$ddRows="true"}{else}{$ddRows="false"}{/if}

    var grid = new wcmf.grid.Grid();
    grids['{$role}Parent'] = grid;
    var curColumnDefs = [];
    {count_items varname="numColumns" array=$template->getDisplayValues(true)}
    {math equation="355/x" x=$numColumns assign="columnWidth" format="%d"}
    {foreach key=name item=value from=$template->getDisplayValues(true)}
    curColumnDefs.push({ldelim}id:"{$name|default:"-"}", dataIndex:"{$name}", header:"{$name|default:"-"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});
    {/foreach}
    columDefs['{$role}Parent'] = curColumnDefs;

    var curButtonDefs = [];
    {if $template->getProperty('canAssociate') == true}
      var dlg{$role}Parent = new AssociateDialog();
      curButtonDefs.push({ldelim}icon:'images/link.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Associate selected"}'{rdelim}, handler:function(){ldelim}dlg{$role}Parent.show('{$role}', grids['{$role}Parent'], '{$node->getOID()}', 'parent', true);{rdelim}{rdelim});
    {/if}
    buttonDefs['{$role}Parent'] = curButtonDefs;

    grids['{$role}Parent'].init('{$template->getObjectDisplayName()} [{$template->getRole($node->getOID())}]', '{$template->getType()}', "{$obfuscator->obfuscate($nodeUtil->getParentQuery($role, $node))}", columDefs['{$role}Parent'], {ldelim}paging:false, autoheight:true, singleSelect:true, ddRows:{$ddRows}{rdelim}, [new wcmf.grid.EditAction()], buttonDefs['{$role}Parent']);
    grids['{$role}Parent'].getGridImpl().applyToMarkup('{$role}ParentGrid');
    grids['{$role}Parent'].load();
  {/foreach}

  // children
  {foreach item=template from=$possiblechildren}
    {$role=$template->getRole($node->getOID())}
    {$realSubject=$template}
    {if $template->getProperty('realSubject')}
      {$realSubject=$template->getProperty('realSubject')}
    {/if}
    {if $template->hasValue('sortkey')}{assign var="ddRows" value="true"}{else}{assign var="ddRows" value="false"}{/if}

    var grid = new wcmf.grid.Grid();
    grids['{$role}Child'] = grid;
    var curColumnDefs = [];
    {count_items varname="numColumns" array=$realSubject->getDisplayValues(true)}
    {math equation="361/x" x=$numColumns assign="columnWidth" format="%d"}
    {foreach key=name item=value from=$realSubject->getDisplayValues(true)}
    curColumnDefs.push({ldelim}id:"{$name|default:"-"}", dataIndex:"{$name}", header:"{$name|default:"-"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});
    {/foreach}
    columDefs['{$role}Child'] = curColumnDefs;

    var curButtonDefs = [];
    {if $template->getProperty('canCreate')}
      {$poid=$node->getOID()}
      {if $template->getProperty('composition') || $template->getProperty('aggregation')}
      curButtonDefs.push({ldelim}icon:'images/new.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Create new %1%" r1=$nodeUtil->getDisplayNameFromType($realSubject->getType())}'{rdelim}, handler:function(){ldelim}doSetParent('{$poid}'); doNew('{$realSubject->getType()}'); setVariable('newrole', '{$role}'); setContext('{$realSubject->getType()}'); submitAction('new');{rdelim}{rdelim});
      {/if}
      {if !$template->getProperty('composition')}
      var dlg{$role}Child = new AssociateDialog();
      curButtonDefs.push({ldelim}icon:'images/link.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Associate selected"}'{rdelim}, handler:function(){ldelim}dlg{$role}Child.show('{$realSubject->getType()}', grids['{$role}Child'], '{$poid}', 'child', false);{rdelim}{rdelim});
      {/if}
    {/if}
    buttonDefs['{$role}Child'] = curButtonDefs;

    grids['{$role}Child'].init('{$realSubject->getObjectDisplayName()} [{$realSubject->getRole($node->getOID())}]', '{$realSubject->getType()}', '{$obfuscator->obfuscate($nodeUtil->getChildQuery($node, $role))}', columDefs['{$role}Child'], {ldelim}paging:true, autoheight:true, singleSelect:true, ddRows:{$ddRows}{rdelim}, [new wcmf.grid.EditAction(), {if $template->getProperty('canCreate')}new wcmf.grid.DuplicateAction(), {/if}new wcmf.grid.DeleteAction()], buttonDefs['{$role}Child'], {ldelim}ptype:'{$node->getType()}', poid:'{$node->getOID()}', role:'{$role}'{rdelim});
    grids['{$role}Child'].getGridImpl().applyToMarkup('{$role}ChildGrid');
    grids['{$role}Child'].load();
  {/foreach}
{else}
  {if $rootTemplateNode}
    {if $rootTemplateNode->hasValue('sortkey')}{assign var="ddRows" value="true"}{else}{assign var="ddRows" value="false"}{/if}

    var grid = new wcmf.grid.Grid();
    var columDefs = [];
    {count_items varname="numColumns" array=$rootTemplateNode->getDisplayValues(true)}
    {math equation="571/x" x=$numColumns assign="columnWidth" format="%d"}
    {foreach key=name item=value from=$rootTemplateNode->getDisplayValues(true)}
    columDefs.push({ldelim}id:"{$name|default:"-"}", dataIndex:"{$name}", header:"{$name|default:"-"}", width:{$columnWidth}, sortable:true, renderer:grid.renderColumnDefault.createDelegate(grid){rdelim});
    {/foreach}

    var buttonDefs = [];
    buttonDefs.push({ldelim}icon:'images/new.png', cls:'x-btn-icon', tooltip:{ldelim}text:'{translate text="Create new %1%" r1=$nodeUtil->getDisplayNameFromType($rootType)}'{rdelim}, handler:function(){ldelim}doSetParent('{$oid}'); doNew('{$rootType}'); submitAction('new');{rdelim}{rdelim});

    grid.init('{$nodeUtil->getDisplayNameFromType($rootType)}', '{$rootType}', '{$obfuscator->obfuscate($nodeUtil->getNodeQuery($rootType))}', columDefs, {ldelim}paging:true, autoheight:true, singleSelect:true, ddRows:{$ddRows}{rdelim}, [new wcmf.grid.EditAction(), new wcmf.grid.DuplicateAction(), new wcmf.grid.DeleteAction()], buttonDefs);
    grid.getGridImpl().applyToMarkup('{$rootType}Grid');
    grid.load();
  {/if}
{/if}
});
</script>
{/block}

{block name=title append}
<div id="tabnav">
  {include file="lib:application/views/include/root_type_tabs.tpl" rootType=$rootType}
</div>
{/block}

{block name=content}
  {if $lockMsg != ''}
<div class="hint">{translate text="some objects are locked"} (<a href="javascript:displayMsg();">details</a>)</div>
<div class="hint" id="msg">{$lockMsg}</div>
  {/if}

  {if $viewMode == 'detail'}
  {*------------------------------- Detail View -------------------------------*}

<div id="leftcol">

  {*------ Edit ------*}
<div class="contentblock">
  <h2 title="{translate text="object ID"}: {$oid|default:"-"}">{$node->getDisplayValue(true)}&nbsp;</h2>
  <span class="spacer"></span>
  {$value_names=$node->getValueNames($cur_data_type)}
  {section name=value_name_index loop=$value_names}
    {$cur_value_name=$value_names[value_name_index]}
  <span class="dottedSeparator"></span>
  <span class="left" title="{$node->getValueDescription($cur_value_name, $cur_data_type)}">{$node->getValueDisplayName($cur_value_name, $cur_data_type)}</span>
  <span class="right">{$nodeUtil->getInputControl($node, $cur_value_name, $cur_data_type)}</span>
  {/section}
  <span class="spacer"></span>
  {foreach item=template from=$possibleparents}
    {$role=$template->getRole($node->getOID())}
    {$parent=$template->getProperty('assignedParent')}
    {if $parent}
      {$parentoid=$parent->getOID()}
      {$parenttype=$nodeUtil->getDisplayNameFromType($parent->getType())}
      {translate text="Create new '%1%' under '%2%'" r1=$nodeUtil->getDisplayNameFromType($node->getType()) r2=$parenttype varname="createText"}
  <span class="all"><a href="javascript:doSetParent('{$parentoid}'); doNew('{$node->getType()}'); setContext('{$node->getType()}'); submitAction('new');"><img src="images/new.png" 
    alt="{$createText}" title="{$createText}" border="0"> {$createText}</a></span>
    {/if}
  {foreachelse}
    {translate text="Create new %1%" r1=$nodeUtil->getDisplayNameFromType($node->getType()) varname="createText"}
  <span class="all"><a href="javascript:doSetParent(''); doNew('{$node->getType()}'); setContext('{$node->getType()}'); submitAction('new');"><img src="images/new.png" 
    alt="{$createText}" title="{$createText}" border="0"> {$createText}</a></span>
  {/foreach}
</div>

</div>
<div id="rightcol">

  {*------ Parents ------*}
  {foreach item=template from=$possibleparents}
    {$role=$template->getRole($node->getOID())}
<div class="contentblock">
  <div id="{$role}ParentGrid" style="border:1px solid #99bbe8;overflow: hidden; width: 445px"></div>
</div>
  {/foreach}

  {*------ Children grouped by role ------*}
  {foreach item=template from=$possiblechildren}
    {$role=$template->getRole($node->getOID())}
<div class="contentblock">
  <div id="{$role}ChildGrid" style="border:1px solid #99bbe8;overflow: hidden; width: 445px"></div>
</div>
  {/foreach}

</div>

  {else}
  {*------------------------------- Overview -------------------------------*}

<div class="contentblock">
  <div id="{$rootType}Grid" style="border:1px solid #99bbe8;overflow: hidden; width: 665px;"></div>
</div>

  {/if}
{/block}
