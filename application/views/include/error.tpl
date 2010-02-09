{* uses the following variables: displayMessageDialog ("true"|"false") *}

{if $errorMsg != ''}
{if $displayMessageDialog == "true"}
<script language="Javascript">
<!-- 
  alert('ERROR: {$errorMsg|escape:"quotes"|strip_tags|strip:" "}'); 
//-->
</script>
{/if}
<div class="error">{translate text="Error"}: {$errorMsg}</div>
{/if}
