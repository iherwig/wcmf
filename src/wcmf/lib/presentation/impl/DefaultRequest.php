<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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
 * Predefined errors
 */
$message = ObjectFactory::getInstance('message');
define('ROUTE_NOT_FOUND', serialize(array('ROUTE_NOT_FOUND', ApplicationError::LEVEL_ERROR, 404,
  $message->getText('No route matching the request path can be found.')
)));
define('METHOD_NOT_ALLOWED', serialize(array('METHOD_NOT_ALLOWED', ApplicationError::LEVEL_ERROR, 405,
  $message->getText('The HTTP method is not allowed on the requested path.')
)));

/**
 * Default Request implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultRequest extends AbstractControllerMessage implements Request {

  private $response = null;
  private $responseFormat = null;
  private $method = null;

  private static $logger = null;

  /**
   * Constructor
   * @param $formatter
   */
  public function __construct(Response $response, Formatter $formatter) {
    parent::__construct($formatter);
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }

    // initialize response
    $this->response = $response;
    $this->response->setRequest($this);

    // add header values to request
    foreach (self::getAllHeaders() as $name => $value) {
      $this->setHeader($name, $value);
    }

    // fix get request parameters
    if (isset($_SERVER['QUERY_STRING'])) {
      self::fix($_GET, $_SERVER['QUERY_STRING']);
    }
    if (isset($_SERVER['QUERY_STRING'])) {
      self::fix($_COOKIE, $_SERVER['COOKIES']);
    }
    $requestBody = file_get_contents("php://input");
    if ($this->getFormat() != 'json') {
      self::fix($_POST, $requestBody);
    }
    else {
      $_POST = json_decode($requestBody, true);
    }

    $this->method = isset($_SERVER['REQUEST_METHOD']) ?
            strtoupper($_SERVER['REQUEST_METHOD']) : '';
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
    if (self::$logger->isDebugEnabled()) {
      self::$logger->debug("Request path: ".$requestPath);
    }
    $config = ObjectFactory::getInstance('configuration');

    $baseRequestData = array();
    $defaultValuePattern = '([^/]+)';
    $method = $this->getMethod();
    $routeFound = false;
    $methodAllowed = false;
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
        $params = array();
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
        $routePattern = str_replace(array('*', '/'), array('.*', '\/'), $routePattern);

        // try to match the current request path
        if (self::$logger->isDebugEnabled()) {
          self::$logger->debug("Check path: ".$route." -> ".$routePattern);
        }
        $matches = array();
        if (preg_match('/^'.$routePattern.'\/?$/', $requestPath, $matches)) {
          if (self::$logger->isDebugEnabled()) {
            self::$logger->debug("Match");
          }
          // set parameters from request path
          for ($i=0, $count=sizeof($params); $i<$count; $i++) {
            $baseRequestData[$params[$i]] = isset($matches[$i+1]) ? $matches[$i+1] : null;
          }
          // set parameters from request definition (overriding path parameters)
          $requestDefData = array();
          parse_str($requestDef, $requestDefData);
          $baseRequestData = array_merge($baseRequestData, $requestDefData);
          $routeFound = true;

          // check if method is allowed
          if ($allowedMethods == null || in_array($method, $allowedMethods)) {
            $methodAllowed = true;
            break;
          }
        }
      }
    }

    $userInfo = array('ip' => $_SERVER['REMOTE_ADDR'],
        'agent' => $_SERVER['HTTP_USER_AGENT'],
        'referrer' => $_SERVER['HTTP_REFERER']);

    if (!$routeFound) {
      throw new ApplicationException($this, $this->response,
              ApplicationError::get('ROUTE_NOT_FOUND', array_merge(
                      $userInfo, array('route' => $requestPath))));
    }

    // check if method is allowed
    if (!$methodAllowed) {
      throw new ApplicationException($this, $this->response,
              ApplicationError::get('METHOD_NOT_ALLOWED', array_merge(
                      $userInfo, array('method' => $method, 'route' => $requestPath))));
    }

    // get request data
    $requestData = array();
    switch ($method) {
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
            (isset($baseRequestData['controller']) ? $baseRequestData['controller'] : $controller);

    $context = isset($requestData['context']) ?
            filter_var($requestData['context'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($baseRequestData['context']) ? $baseRequestData['context'] : $context);

    $action = isset($requestData['action']) ?
            filter_var($requestData['action'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($baseRequestData['action']) ? $baseRequestData['action'] : $action);

    // setup request
    $this->setSender($controller);
    $this->setContext($context);
    $this->setAction($action);
    $this->setValues(array_merge($baseRequestData, $requestData));
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
   * @see Request::getResponse()
   */
  public function getResponse() {
    return $this->response;
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
   * Get all http headers
   * @return Associative array
   */
  private static function getAllHeaders() {
    $headers = array();
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
    $keys = array();

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
      $target = array();
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
}
?>
