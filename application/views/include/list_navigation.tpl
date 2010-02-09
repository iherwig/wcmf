{assign var="startIndex" value=$index+1}
{if $startIndex > $index+$size}
  {assign var="startIndex" value=$index+$size}
{/if}

	<span class="all">
{if $size > 0}
    {$startIndex}-{$index+$size} {translate text="of"} {$total} | 
{if $hasPrev}
		<a href="javascript:submitAction('prev');">{translate text="prev"}</a> 
{else}
		{translate text="prev"}
{/if}
{section name=package_index loop=$packageStartOids}
{if $size > 0 && $nodes.0->getOID() != $packageStartOids[package_index]}
		<a href="javascript:doDisplay('{$packageStartOids[package_index]}'); submitAction('jump');">{$smarty.section.package_index.iteration} </a>
{else}
		<span class="grey">{$smarty.section.package_index.iteration}</span>
{/if}      
{/section}
{if $hasNext}
		<a href="javascript:submitAction('next');">{translate text="next"}</a> 
{else}
		{translate text="next"} 
{/if}
    | <a href="javascript:submitAction('');">{translate text="show"}</a> <input type="text" name="pageSize" value="{$pageSize}" class="tiny">
{/if}
	</span>
