<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\format\Format;
use wcmf\lib\presentation\impl\AbstractControllerMessage;
use wcmf\lib\presentation\Request;
use wcmf\lib\util\StringUtil;

/**
 * Default Request implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultRequest extends AbstractControllerMessage implements Request {

  /**
   * The format of the response (used for de-, serialization).
   */
  private $_responseFormat = null;

  /**
   * The HTTP method of the request
   */
  private $_method = null;

  private static $_logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
    // add header values to request
    foreach (self::getAllHeaders() as $name => $value) {
      $this->setHeader($name, $value);
    }
    // fix get request parameters
    if (isset($_SERVER['QUERY_STRING'])) {
      self::fix($_GET, $_SERVER['QUERY_STRING']);
    }
    $this->_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
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
   * / = action=cms
   * /rest/{language}/{className} = action=restAction&collection=1
   * /rest/{language}/{className}/{id|[0-9]+} = action=restAction&collection=0
   * @endcode
   */
  public function initialize($controller=null, $context=null, $action=null) {
    // get base request data from request path
    $basePath = preg_replace('/\/?[^\/]*$/', '', $_SERVER['SCRIPT_NAME']);
    $requestUri = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
    $requestPath = preg_replace('/^'.StringUtil::escapeForRegex($basePath).'/', '', $requestUri);
    if (self::$_logger->isDebugEnabled()) {
      self::$_logger->debug("Request path: ".$requestPath);
    }
    $config = ObjectFactory::getInstance('configuration');

    $baseRequestValues = array();
    $defaultValuePattern = '([^/]+)';
    if ($config->hasSection('routes')) {
      $routes = $config->getSection('routes');
      foreach ($routes as $pattern => $requestDef) {

        // prepare route match pattern and extract parameters
        $params = array();
        $pattern = preg_replace_callback('/\{([^\}]+)\}/', function ($match) use($defaultValuePattern, &$params) {
          // a variabel may be either defined by {name} or by {name|pattern} where
          // name is the variable's name and pattern is an optional regex pattern, the
          // values should match
          $paramParts = explode('|', $match[1]);
          // add the variable name to the parameter list
          $params[] = $paramParts[0];
          // return the value match pattern (defaults to defaultValuePattern)
          return sizeof($paramParts) > 1 ? '('.$paramParts[1].')' : $defaultValuePattern;
        }, $pattern);
        $pattern = '/^'.str_replace('/', '\/', $pattern).'\/?$/';

        // try to match the currrent request path
        if (self::$_logger->isDebugEnabled()) {
          self::$_logger->debug("Check path: ".$pattern);
        }
        $matches = array();
        if (preg_match($pattern, $requestPath, $matches)) {
          if (self::$_logger->isDebugEnabled()) {
            self::$_logger->debug("Match");
          }
          // set parameters from request definition
          parse_str($requestDef, $baseRequestValues);
          // set parameters from request path
          for ($i=0, $count=sizeof($params); $i<$count; $i++) {
            $baseRequestValues[$params[$i]] = isset($matches[$i+1]) ? $matches[$i+1] : null;
          }
          break;
        }
      }
    }

    // get additional request data from parameters
    $requestValues = array_merge($_GET, $_POST, $_FILES);
    $rawPostBody = file_get_contents('php://input');
    // add the raw post data if they are json encoded
    $json = json_decode($rawPostBody, true);
    if (is_array($json)) {
      foreach ($json as $key => $value) {
        $requestValues[$key] = $value;
      }
    }

    // get controller/context/action triple
    $controller = isset($requestValues['controller']) ?
            filter_var($requestValues['controller'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($baseRequestValues['controller']) ? $baseRequestValues['controller'] : $controller);

    $context = isset($requestValues['context']) ?
            filter_var($requestValues['context'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($baseRequestValues['context']) ? $baseRequestValues['context'] : $context);

    $action = isset($requestValues['action']) ?
            filter_var($requestValues['action'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW) :
            (isset($baseRequestValues['action']) ? $baseRequestValues['action'] : $action);

    // setup request
    $this->setSender($controller);
    $this->setContext($context);
    $this->setAction($action);
    $this->setValues(array_merge($baseRequestValues, $requestValues));
  }

  /**
   * @see Request::getMethod()
   */
  public function getMethod() {
    return $this->_method;
  }

  /**
   * @see Request::setResponseFormat()
   */
  public function setResponseFormat(Format $format) {
    $this->_responseFormat = $format;
  }

  /**
   * @see Request::setResponseFormatByName()
   */
  public function setResponseFormatByName($name) {
    $formats = self::getFormats();
    if (!isset($formats[$name])) {
      throw new ConfigurationException("Configuration section 'Formats' does not contain a format definition for: ".$name);
    }
    $this->setResponseFormat($formats[$name]);
  }

  /**
   * @see Request::getResponseFormat()
   */
  public function getResponseFormat() {
    if ($this->_responseFormat == null) {
      $this->_responseFormat = self::getFormatFromMimeType($this->getHeader('Accept'));
    }
    return $this->_responseFormat;
  }

  /**
   * Get a string representation of the message
   * @return The string
   */
  public function __toString() {
    $str = 'method='.$this->_method.', ';
    $str .= 'responseformat='.get_class($this->_responseFormat).', ';
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
