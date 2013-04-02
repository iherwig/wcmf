<?php
error_reporting(E_ALL | E_PARSE);

require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use \Exception;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Application;
use wcmf\lib\util\URIUtil;

$application = new Application();
try {
  // initialize the application
  $application->initialize('../config/');

  // check for authenticated user
  $permissionManager = ObjectFactory::getInstance('permissionManager');
  $isLoggedIn = $permissionManager->getAuthUser() != null;

  // get configuration
  $config = ObjectFactory::getConfigurationInstance();
  $appTitle = $config->getValue('applicationTitle', 'application');
  $rootTypes = $config->getValue('rootTypes', 'application');

  // check if the user should be redirected to the login page
  // if yes, we do this and add the requested path as route parameter
  $pathPrefix = dirname($_SERVER['SCRIPT_NAME']);
  $basePath = dirname($_SERVER['SCRIPT_NAME']).'/';
  $requestPath = str_replace($basePath, '', $_SERVER['REQUEST_URI']);
  if (!$isLoggedIn && strlen($requestPath) > 0 && !preg_match('/\?route=/', $_SERVER['REQUEST_URI'])) {
    if ($requestPath != 'logout') {
      $redirectUrl = URIUtil::getProtocolStr().$_SERVER['HTTP_HOST'].$basePath.'?route='.$requestPath;
      header("Location: ".$redirectUrl);
    }
  }
}
catch (Exception $ex) {
  $application->handleException($ex);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo $appTitle; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link href="css/app.css" rel="stylesheet" media="screen">

    <script>
      document.write('<style media="all">#static { display: none; }</style>');
    </script>
  </head>

  <body>
    <script>
      var appConfig = {
          title: '<?php echo $appTitle; ?>',
          rootTypes: [<?php if ($rootTypes && sizeof($rootTypes) > 0) { echo "'".join("', '", $rootTypes)."'"; } ?>],
          pathPrefix: '<?php echo $pathPrefix; ?>'
      };
      var dojoConfig = {
          has: {
              "dijit": true
          },
          baseUrl: '',
          async: 1,
          tlmSiblingOfDojo: 0,
          isDebug: 1,
          packages: [
              { name: 'dojo', location: 'vendor/dojo/dojo', map: {} },
              { name: 'dijit', location: 'vendor/dojo/dijit', map: {} },
              { name: 'dojox', location: 'vendor/dojo/dojox', map: {} },
              { name: 'bootstrap', location: 'vendor/dojo-bootstrap' },
              { name: 'routed', location: 'vendor/routed' },
              { name: 'dojomat', location: 'vendor/dojomat' },
              { name: 'dgrid', location: 'vendor/dgrid' },
              { name: 'xstyle', location: 'vendor/xstyle' },
              { name: 'put-selector', location: 'vendor/put-selector' },

              { name: 'app', location: 'js', map: {} }
          ],
          'routing-map': {
              pathPrefix: appConfig.pathPrefix
          }
      };
    </script>

    <script src="vendor/dojo/dojo/dojo.js"></script>

    <script>
      require(["app/App"], function (App) { new App(); });
    </script>

    <div id="static" class="alert alert-error">
      <strong>Warning!</strong> Please enable JavaScript in your browser.
    </div>

    <div id="wrap">
      <div id="push"></div>
    </div>
    <div id="footer">
      <div class="container">
        <p class="pull-right muted">created with
          <a href="http://sourceforge.net/projects/wcmf/" target="_blank">wCMF</a></p>
        <p class="muted">&copy; 2013</p>
      </div>
    </div>

  </body>
</html>