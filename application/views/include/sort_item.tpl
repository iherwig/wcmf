{* this template knows the following variables: node *}
{if $node->getValue('hasSortUp')}
      <a href="javascript:setVariable('sortoid', '{$node->getOID()}'); setVariable('prevoid', '{$node->getValue('prevoid')}'); submitAction('sortup');"><img src="images/up.png" alt="{translate text="Up"}" title="{translate text="Up"}" border="0"></a>
{else}
      <img src="images/up_grey.png" alt="{translate text="Up"}" title="{translate text="Up"}" border="0">
{/if}
{if $node->getValue('hasSortDown')}
      <a href="javascript:setVariable('sortoid', '{$node->getOID()}'); setVariable('nextoid', '{$node->getValue('nextoid')}'); submitAction('sortdown');"><img src="images/down.png" alt="{translate text="Down"}" title="{translate text="Down"}" border="0"></a> 
{else}
      <img src="images/down_grey.png" alt="{translate text="Down"}" title="{translate text="Down"}" border="0">
{/if}
