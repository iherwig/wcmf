{include file="lib:application/views/include/header.tpl"}

{if $errorMsg == ''}
  {if $stepNumber <= $numberOfSteps}
<body onLoad="submitAction('continue');">
  {else}
<body>
<script language="Javascript">
  window.close();
</script>
  {/if}
{/if}

{include file="lib:application/views/include/formheader.tpl"}

{if $errorMsg != ''}
<div class="error">{$errorMsg}</div>
{else}
<div id="popupcontent">
<div>{section name=progress loop=$stepsArray} . {/section}<br />
{section name=progress loop=$stepsArray}{if $smarty.section.progress.iteration <= $stepNumber} . {/if}{/section}</span></div>
{/if}
<div>{$displayText}</div>
</div>
</form>

</body>
</html>
