{configvalue section="cms" key="libDir" varname="libDir"}
<!DOCTYPE html>
<html lang="en">
  <head>
{block name=head}
    <meta charset="utf-8">
    <title>{configvalue key="applicationTitle" section="cms"}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->

    <link href="{$libDir}vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>
    <link href="{$libDir}vendor/bootstrap/css/bootstrap-responsive.css" rel="stylesheet">

    <link href="{$libDir}vendor/dojo/dijit/themes/claro/claro.css" rel="stylesheet">
    <link href="{$libDir}vendor/dojo/dgrid/css/skins/claro.css" rel="stylesheet">

    <link href="style/wcmf.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="images/favicon.ico">

    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="images/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="images/apple-touch-icon-114x114.png">
{/block}
  </head>

  <body class="claro">

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="#">{configvalue key="applicationTitle" section="cms"}</a>
{block name=navigation}{/block}
        </div>
      </div>
    </div>

    <div class="container">
      <div id="error" class="alert alert-error alert-block" style="display:none">
        <a href="#" class="close">&times;</a>
        <p id="errorMessage"></p>
      </div>
{block name=center}{/block}
      <hr>
      <footer>
        <p>
          {if $authUser != null}{translate text="Logged in as %1% since %2%" r0=$authUser->getLogin() r1=$authUser->getLoginTime()}{/if}
          <span class="wcmfLink">Powered by <a href="http://wcmf.sourceforge.net" target="_blank">wCMF</a></span>
        </p>
      </footer>
    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
{block name=script}
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
    <script src="{$libDir}vendor/dojo/dojo/dojo.js" data-dojo-config="parseOnLoad:false, isDebug:true"></script>
    <script src="{$libDir}vendor/jquery/jquery.js"></script>
    <script src="{$libDir}vendor/bootstrap/js/bootstrap.js"></script>
    <script type="text/javascript" src="js.php?file={$libDir}application/js/net/sourceforge/wcmf/Message.js.php&lang={$lang}"></script>
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
      wcmf.defaultLanguage = '{configvalue key="defaultLanguage" section="i18n"}';

      dojo.addOnLoad(function() {
        // create declarative widgets after code is loaded
        dojo.parser.parse();
      });
    </script>
{/block}

  </body>
</html>










