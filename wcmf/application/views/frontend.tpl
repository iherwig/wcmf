{extends file="lib:application/views/main.tpl"}

{block name=script append}
  <script type="text/javascript" src="main.php?action=model"></script>
  <script type="text/javascript">
  dojo.addOnLoad(function() {

    var rootTypes = [];
  {configvalue key="rootTypes" section="cms" varname="rootTypes"}
  {foreach $rootTypes as $rootType}
    rootTypes.push("{$rootType}");
  {/foreach}

    // create TypeTabContainer instance
    var typeTabContainer = new wcmf.ui.TypeTabContainer({
      rootTypes: rootTypes,
      style: "height: 530px; width: 100%;"
    }, dojo.byId("typeTabContainerDiv"));
    typeTabContainer.startup();
  });
  </script>
{/block}

{block name=center}
  <div class="container">
    <div id="typeTabContainerDiv"></div>
  </div>
{/block}