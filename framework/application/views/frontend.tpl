{extends file="lib:application/views/main.tpl"}

{block name=head append}
<script type="text/javascript" src="main.php?action=model"></script>
{/block}

{block name=content}
<div data-dojo-type="wcmf.ui.TypeTabContainer" id="nodeTabContainer" style="width:100%; height:100%"></div>
{/block}