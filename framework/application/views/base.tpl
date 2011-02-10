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

  <!-- link rel="stylesheet" type="text/css" href="style/style.css" /-->
  <link rel="stylesheet" type="text/css" href="style/wcmf.css" />
  <link rel="stylesheet" type="text/css" href="{$libDir}3rdparty/dojo/dijit/themes/claro/claro.css" />
  <link rel="stylesheet" type="text/css" href="{$libDir}3rdparty/dojo/dojox/grid/enhanced/resources/tundraEnhancedGrid.css" />

  <script src="{$libDir}3rdparty/dojo/dojo/dojo.js" djConfig="parseOnLoad: true"></script>

  <script type="text/javascript">
    dojo.require("dijit.form.Form");
    dojo.require("dijit.form.Button");
    dojo.require("dijit.form.ValidationTextBox");
    dojo.require("dijit.form.CheckBox");
    dojo.require("dijit.layout.BorderContainer");
    dojo.require("dijit.layout.ContentPane");
    dojo.require("dijit.layout.TabContainer");
    dojo.require("dijit.MenuBar");
    dojo.require("dijit.PopupMenuBarItem");
    dojo.require("dijit.Menu");
    dojo.require("dijit.MenuItem");
    dojo.require("dijit.PopupMenuItem");
    
    dojo.require("dojo.fx");

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
  <script type="text/javascript" src="js.php?file={$libDir}application/js/message.js.php"></script>
  <script type="text/javascript" src="{$libDir}application/js/Action.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/Error.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/EasyRestService.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/Request.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/DionysosService.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/persistence/Store.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/Grid.js"></script>
  <script type="text/javascript" src="{$libDir}application/js/ui/DetailPane.js"></script>
{/block}
</head>
<body class="claro">
<div id="mainDiv">
<div dojoType="dijit.layout.BorderContainer" style="width:100%; height:100%" gutters="false">
  <!-- TOP Pane -->
  <div dojoType="dijit.layout.ContentPane" region="top">
{block name=title}
    <div id="head">
      <span><a href="http://wcmf.sourceforge.net" target="_blank"><img src="images/wcmf_logo.gif" width="180" height="54" alt="wcmf logo" border="0" /></a></span>
      <!--
      <span id="title">{configvalue key="applicationTitle" section="cms"}</span>
      <span id="logininfo">{if $authUser != null}{translate text="Logged in as %1% since %2%" r0=$authUser->getLogin() r1=$authUser->getLoginTime()}{/if}</span>
      -->
    </div>
{/block}
    <div id="error">
      <span id="errorMessage">{$errorMessage}</span>
    </div>
{block name=navigation}{/block}
  </div>
  <!-- CENTER Pane -->
  <div dojoType="dijit.layout.ContentPane" region="center">
    <div dojoType="dijit.form.Form" id="mainForm" jsId="mainForm" encType="multipart/form-data"
      action="{$smarty.server.PHP_SELF}" method="post" class="wcmf_form" style="width:100%; height:100%;">
{block name=parameters}{/block}
{block name=content}{/block}
    </div>
  </div>
  <!-- BOTTOM Pane -->
  <!--div dojoType="dijit.layout.ContentPane" region="bottom">
  </div-->
</div>
</div>
</body>
</html>