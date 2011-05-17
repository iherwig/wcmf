{configvalue section="cms" key="libDir" varname="libDir"}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de" xml:lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
{block name=head}
  <title>{configvalue key="applicationTitle" section="cms"}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="cache-control" content="no-cache">
  <meta http-equiv="expires" content="0">

  <meta name="abstract" content="" />
  <meta name="description" content="" />
  <meta name="keywords" content="" />
  <meta name="author" content="wemove digital solutions" />
  <meta name="copyright" content="wemove digital solutions GmbH" />
  <meta name="revisit-after" content="30 days" />
  <meta name="robots" content="INDEX,FOLLOW" />

  <link rel="shortcut icon" href="images/favicon.ico" />
  
  <!-- link rel="stylesheet" type="text/css" href="style/style.css" /-->
  <link rel="stylesheet" type="text/css" href="style/wcmf.css" />
  <link rel="stylesheet" type="text/css" href="style/dojo_theme/wcmf.css" />
  <link rel="stylesheet" type="text/css" href="style/dojo_theme/EnhancedGrid.css" />
  <!--
  <link rel="stylesheet" type="text/css" href="{$libDir}3rdparty/dojo/dijit/themes/claro/claro.css" />
  <link rel="stylesheet" type="text/css" href="{$libDir}3rdparty/dojo/dojox/grid/enhanced/resources/claro/EnhancedGrid.css" />
  -->

  <script>
    /**
     * Setup dojo
     */
    var _dbpDev = true;
    dojoConfig = _dbpDev ? {
      modulePaths : {
        "app": "../../../application/js",
        "wcmf": "../../../application/js/net/sourceforge/wcmf",
        "com.ibm.developerworks": "../../../application/js/com/ibm/developerworks"
      }
    } : {};
  </script>
  <script src="{$libDir}3rdparty/dojo/dojo/dojo.js" data-dojo-config="parseOnLoad:false, isDebug:true"></script>
  <script type="text/javascript" src="js.php?file={$libDir}application/js/net/sourceforge/wcmf/Message.js.php"></script>
  <script type="text/javascript">
    /**
     * Include application classes
     */
    dojo.require("app.base");

    /**
     * Some global variables in the wcmf namespace
     */
    dojo.provide("wcmf");
    wcmf.appURL = '{$smarty.server.PHP_SELF}';
    wcmf.sid = '{$sid}';
    wcmf.controller = '{$controller}';
    wcmf.context = '{$context}';
    wcmf.action = '{$action}';
    wcmf.responseFormat = '{$responseFormat}';
  </script>
{/block}
</head>
<body class="wcmf">
<div id="mainDiv">
  <div id="error">
    <span id="errorMessage">{$errorMessage}</span>
  </div>
  <div data-dojo-type="dijit.layout.BorderContainer" id="borderContainer" style="width:100%; height:100%" data-dojo-props="gutters:false">
    <!-- TOP Pane -->
    <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'top'" style="padding-bottom:0;">
{block name=title}
      <div id="head">
        <span id="logo"><a href="http://wcmf.sourceforge.net" target="_blank"><img src="images/wcmf_logo_new.png" height="71" alt="wcmf logo" border="0" /></a></span>
        <span id="title">{configvalue key="applicationTitle" section="cms"}</span>
        <span id="logininfo">{if $authUser != null}{translate text="Logged in as %1% since %2%" r0=$authUser->getLogin() r1=$authUser->getLoginTime()}{/if}</span>
      </div>
{/block}
{block name=navigation}{/block}
    </div>
    <!-- CENTER Pane -->
    <div data-dojo-type="dijit.layout.ContentPane" data-dojo-props="region:'center'">
{block name=parameters}{/block}
{block name=content}{/block}
    </div>
  </div>
</div>
</body>
</html>