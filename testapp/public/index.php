<?php
error_reporting(E_ALL | E_PARSE);

define('WCMF_BASE', realpath( dirname(__FILE__).'/../..').'/');
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

use \Exception;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Application;

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

  // check if the user should be redirected to the login page
  $pathPrefix = dirname($_SERVER['SCRIPT_NAME']);
  $basePath = dirname($_SERVER['SCRIPT_NAME']).'/';
  $requestPath = str_replace($basePath, '', $_SERVER['REQUEST_URI']);
  if (!$isLoggedIn && strlen($requestPath) > 0 && !preg_match('/\?route=/', $_SERVER['REQUEST_URI'])) {
    if ($requestPath != 'logout') {
      $redirectUrl = $basePath.'?route='.$requestPath;
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
<?php if (isset($redirectUrl)) : ?>
      window.location.href = '<?php echo $redirectUrl; ?>';
<?php endif; ?>
      var dojoConfig = {
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

          { name: 'app', location: 'js', map: {} }
        ],
        'routing-map': {
          pathPrefix: '<?php echo $pathPrefix; ?>'
        }
      };
      var appConfig = {
        title: '<?php echo $appTitle; ?>'
      };
    </script>

    <script src="vendor/dojo/dojo/dojo.js"></script>

    <script>
      require(['app/App'], function (App) { new App(); });
    </script>

    <div id="static" class="alert alert-error">
      <strong>Warning!</strong> Please enable JavaScript in your browser.
    </div>
  </body>
</html>