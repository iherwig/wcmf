<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\impl\AbstractControllerMessage;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\util\StringUtil;

/**
 * Default Request implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultRequest extends AbstractControllerMessage implements Request {

  private $response = null;
  private $responseFormat = null;
  private $method = null;

  private static $requestDataFixed = false;
  private static $logger = null;
  private static $errorsDefined = false;

  /**
   * Constructor
   * @param $formatter
   */
  public function __construct(Formatter $formatter) {
    parent::__construct($formatter);
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    self::defineErrors();

    // set headers and method
    foreach (self::getAllHeaders() as $name => $value) {
      $this->setHeader($name, $value);
    }
    $this->method = isset($_SERVER['REQUEST_METHOD']) ?
            strtoupper($_SERVER['REQUEST_METHOD']) : '';

    // fix get request parameters
    if (!self::$requestDataFixed) {
      if (isset($_SERVER['QUERY_STRING'])) {
        self::fix($_GET, $_SERVER['QUERY_STRING']);
      }
      if (isset($_SERVER['COOKIES'])) {
        self::fix($_COOKIE, $_SERVER['COOKIES']);
      }
      $requestBody = file_get_contents("php://input");
      if ($this->getFormat() != 'json') {
        self::fix($_POST, $requestBody);
      }
      else {
        $_POST = json_decode($requestBody, true);
      }
      self::$requestDataFixed = true;
    }
  }

  /**
   * @see Response::setResponse()
   */
  public function setResponse(Response $response) {
    $this->response = $response;
    if ($response->getRequest() !== $this) {
      $response->setRequest($this);
    }
  }

  /**
   * @see Request::getResponse()
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * @see Request::initialize()
   *
   * The method tries to match the current request path against the routes
   * defined in the configuration section 'routes' and constructs the request based on
   * these parameters. It then adds all data contained in $_GET, $_POST, $_FILES and
   * php://input (raw data from the request body).
   *
   * Examples for route definitions are:
   * @code
   * GET/ = action=cms
   * GET,POST,PUT,DELETE/rest/{language}/{className} = action=restAction&collection=1
   * GET,POST,PUT,DELETE/rest/{language}/{className}/{id|[0-9]+} = action=restAction&collection=0
   * @endcode
   */
  public function initialize($controller=null, $context=null, $action=null) {
    // get base request data from request path
    $basePath = preg_replace('/\/?[^\/]*$/', '', $_SERVER['SCRIPT_NAME']);
    $requestUri = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
    $requestPath = preg_replace('/^'.StringUtil::escapeForRegex($basePath).'/', '', $requestUri);
    $requestMethod = $this->getMethod();
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Request: ".$requestMethod." ".$requestPath);
    }

    // get all routes from the configuration that match the request path
    $matchingRoutes = $this->getMatchingRoutes($requestPath);
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Matching routes:");
      self::$logger->debug($matchingRoutes);
    }

    // get client info error for logging
    $clientInfo = [
      'ip' => $_SERVER['REMOTE_ADDR'],
      'agent' => $_SERVER['HTTP_USER_AGENT'],
      'referrer' => $_SERVER['HTTP_REFERER']
    ];

    // check if the requested route matches any configured route
    if (sizeof($matchingRoutes) == 0) {
      throw new ApplicationException($this, $this->getResponse(),
              ApplicationError::get('ROUTE_NOT_FOUND', array_merge(
                      $clientInfo, ['route' => $requestPath])));
    }

    // get the best matching route
    $route = $this->getBestRoute($matchingRoutes);
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Best route:");
      self::$logger->debug($route);
    }

    // check if method is allowed
    $allowedMethods = $route['methods'];
    if ($allowedMethods != null && !in_array($requestMethod, $allowedMethods)) {
      throw new ApplicationException($this, $this->getResponse(),
              ApplicationError::get('METHOD_NOT_ALLOWED', array_merge(
                      $clientInfo, ['method' => $requestMethod, 'route' => $requestPath])));
    }

    // get request parameters from route
    $pathRequestData = $route['parameters'];

    // get other request data
    $requestData = [];
    switch ($requestMethod) {
      case 'GET':
        $requestData = $_GET;
        break;
      case 'POST':
      case 'PUT':
        $requestData = $_POST;
        break;
    }

    // get controller/context/action triple
    $controller = isset($requestData['controller']) ?
            filter_var($requestData['controller'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($pathRequestData['controller']) ? $pathRequestData['controller'] : $controller);

    $context = isset($requestData['context']) ?
            filter_var($requestData['context'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($pathRequestData['context']) ? $pathRequestData['context'] : $context);

    $action = isset($requestData['action']) ?
            filter_var($requestData['action'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($pathRequestData['action']) ? $pathRequestData['action'] : $action);

    // setup request
    $this->setSender($controller);
    $this->setContext($context);
    $this->setAction($action);
    $this->setValues(array_merge($pathRequestData, $requestData));
  }

  /**
   * @see Request::getMethod()
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * @see Request::setResponseFormat()
   */
  public function setResponseFormat($format) {
    $this->responseFormat = $format;
  }

  /**
   * @see Request::getResponseFormat()
   */
  public function getResponseFormat() {
    if ($this->responseFormat == null) {
      $this->responseFormat = $this->getFormatter()->getFormatFromMimeType($this->getHeader('Accept'));
    }
    return $this->responseFormat;
  }

  /**
   * Get a string representation of the message
   * @return The string
   */
  public function __toString() {
    $str = 'method='.$this->method.', ';
    $str .= 'responseformat='.$this->responseFormat.', ';
    $str .= parent::__toString();
    return $str;
  }

  /**
   * Get all routes from the Routes configuration section that match the given
   * request path
   * @param $requestPath
   * @return Array of arrays with keys 'numPathParameters', 'parameters', 'methods'
   */
  protected function getMatchingRoutes($requestPath) {
    $matchingRoutes = [];
    $defaultValuePattern = '([^/]+)';

    $config = ObjectFactory::getInstance('configuration');
    if ($config->hasSection('routes')) {
      $routes = $config->getSection('routes');
      foreach ($routes as $route => $requestDef) {
        // extract allowed http methods
        $allowedMethods = null;
        if (strpos($route, '/') !== false) {
          list($methodStr, $route) = explode('/', $route, 2);
          $allowedMethods = preg_split('/\s*,\s*/', trim($methodStr));
          $route = '/'.trim($route);
        }

        // extract parameters from route definition and prepare as regex pattern
        $params = [];
        $routePattern = preg_replace_callback('/\{([^\}]+)\}/', function ($match)
                use($defaultValuePattern, &$params) {
          // a variable may be either defined by {name} or by {name|pattern} where
          // name is the variable's name and pattern is an optional regex pattern, the
          // values should match
          $paramParts = explode('|', $match[1], 2);
          // add the variable name to the parameter list
          $params[] = $paramParts[0];
          // return the value match pattern (defaults to defaultValuePattern)
          return sizeof($paramParts) > 1 ? '('.$paramParts[1].')' : $defaultValuePattern;
        }, $route);

        // replace wildcard character and slashes
        $routePattern = str_replace(['*', '/'], ['.*', '\/'], $routePattern);

        // try to match the current request path
        if (self::$logger->isDebugEnabled()) {
          self::$logger->debug("Check path: ".$route." -> ".$routePattern);
        }
        $matches = [];
        if (preg_match('/^'.$routePattern.'\/?$/', $requestPath, $matches)) {
          if (self::$logger->isDebugEnabled()) {
            self::$logger->debug("Match");
          }
          // ignore first match
          array_shift($matches);

          // collect request variables
          $requestParameters = [];

          // 1. path variables
          for ($i=0, $count=sizeof($params); $i<$count; $i++) {
            $requestParameters[$params[$i]] = isset($matches[$i]) ? $matches[$i] : null;
          }

          // 2. parameters from route configuration (overriding path parameters)
          $requestDefData = [];
          parse_str($requestDef, $requestDefData);
          $requestParameters = array_merge($requestParameters, $requestDefData);

          // store match
          $matchingRoutes[] = [
            'route' => $route,
            'numPathParameters' => (preg_match('/\*/', $route) ? PHP_INT_MAX : sizeof($params)),
            'parameters' => $requestParameters,
            'methods' => $allowedMethods
          ];
        }
      }
    }
    return $matchingRoutes;
  }

  /**
   * Get the best matching route from the given list of routes
   * @param $routes Array of route definitions as returned by getMatchingRoutes()
   * @return Array with keys 'numPathParameters', 'parameters', 'methods'
   */
  protected function getBestRoute($routes) {
    // order matching routes by number of parameters
    $method = $this->getMethod();
    usort($routes, function($a, $b) use ($method) {
      $numParamsA = $a['numPathParameters'];
      $numParamsB = $b['numPathParameters'];
      if ($numParamsA == $numParamsB) {
        $hasMethodA = in_array($method, $a['methods']);
        $hasMethodB = in_array($method, $b['methods']);
        return ($hasMethodA && !$hasMethodB) ? -1 :
                ((!$hasMethodA && $hasMethodB) ? 1 : 0);
      }
      return ($numParamsA > $numParamsB) ? 1 : -1;
    });

    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Ordered routes:");
      self::$logger->debug($routes);
    }
    // return most specific route (minimum number of parameters)
    return array_shift($routes);
  }

  /**
   * Get all http headers
   * @return Associative array
   */
  private static function getAllHeaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
        $headers[$name] = $value;
      }
      else if ($name == "CONTENT_TYPE") {
        $headers["Content-Type"] = $value;
      }
      else if ($name == "CONTENT_LENGTH") {
        $headers["Content-Length"] = $value;
      }
    }
    return $headers;
  }

  /**
   * Fix request parameters (e.g. PHP replaces dots by underscore)
   * Code from http://stackoverflow.com/questions/68651/get-php-to-stop-replacing-characters-in-get-or-post-arrays/18163411#18163411
   * @param $target
   * @param $source
   * @param $keep
   */
  private static function fix(&$target, $source, $keep=false) {
    if (!$source) {
      return;
    }
    $keys = [];

    $source = preg_replace_callback(
      '/
      # Match at start of string or &
      (?:^|(?<=&))
      # Exclude cases where the period is in brackets, e.g. foo[bar.blarg]
      [^=&\[]*
      # Affected cases: periods and spaces
      (?:\.|%20)
      # Keep matching until assignment, next variable, end of string or
      # start of an array
      [^=&\[]*
      /x',
      function ($key) use (&$keys) {
        $keys[] = $key = base64_encode(urldecode($key[0]));
        return urlencode($key);
    },
      $source
    );

    if (!$keep) {
      $target = [];
    }

    parse_str($source, $data);
    foreach ($data as $key => $val) {
      // Only unprocess encoded keys
      if (!in_array($key, $keys)) {
        $target[$key] = $val;
        continue;
      }

      $key = base64_decode($key);
      $target[$key] = $val;

      if ($keep) {
        // Keep a copy in the underscore key version
        $key = preg_replace('/(\.| )/', '_', $key);
        $target[$key] = $val;
      }
    }
  }

  /**
   * Define errors
   */
  private static function defineErrors() {
    if (!self::$errorsDefined) {
      $message = ObjectFactory::getInstance('message');
      define('ROUTE_NOT_FOUND', serialize(['ROUTE_NOT_FOUND', ApplicationError::LEVEL_WARNING, 404,
        $message->getText('No route matching the request path can be found.')
      ]));
      define('METHOD_NOT_ALLOWED', serialize(['METHOD_NOT_ALLOWED', ApplicationError::LEVEL_WARNING, 405,
        $message->getText('The HTTP method is not allowed on the requested path.')
      ]));
      self::$errorsDefined = true;
    }
  }
}
?>
