<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ControllerMessage;
use wcmf\lib\presentation\format\Format;

/**
 * Request holds the request values that are used as input to
 * Controller instances. It is typically instantiated and filled by the
 * ActionMapper.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Request extends ControllerMessage {

  private $_responseFormat = null;

  /**
   * Constructor
   * @see ControllerMessage::__construct
   */
  public function __construct($sender, $context, $action) {
    parent::__construct($sender, $context, $action);
    // add header values to request
    foreach (self::getAllHeaders() as $name => $value) {
      $this->setHeader($name, $value);
    }
  }

  /**
   * Get the default request.
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
   *
   * @param $controller The controller to call if none is given in request parameters (optional)
   * @param $context The context to set if none is given in request parameters (optional)
   * @param $action The action to perform if none is given in request parameters (optional)
   * @return Request
   */
  public static function getDefault($controller=null, $context=null, $action=null) {

    // get base request data from request path
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $requestPath = str_replace($basePath, '', preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']));
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug("Request path: ".$requestPath, __CLASS__);
    }
    $config = ObjectFactory::getConfigurationInstance();

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
        if (Log::isDebugEnabled(__CLASS__)) {
          Log::debug("Check patther: ".$pattern, __CLASS__);
        }
        $matches = array();
        if (preg_match($pattern, $requestPath, $matches)) {
          if (Log::isDebugEnabled(__CLASS__)) {
            Log::debug("Match", __CLASS__);
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
    $request = new Request($controller, $context, $action);
    $request->setValues(array_merge($baseRequestValues, $requestValues));

    return $request;
  }

  /**
   * Get the HTTP method of the request
   * @return String
   */
  public function getMethod() {
    return $_SERVER['REQUEST_METHOD'];
  }

  /**
   * Set the desired response format
   * @param $format Format instance
   */
  public function setResponseFormat(Format $format) {
    $this->_responseFormat = $format;
  }

  /**
   * Get the message response format. If no explicit format is set, the
   * format is derived from the Content-Type header value, if existing.
   * If no format can be derived, the first format in the configuration
   * key 'Formats' will be used.
   * @return Format instance
   */
  public function getResponseFormat() {
    if ($this->_responseFormat == null) {
      $this->_responseFormat = self::getFormatFromMimeType($this->getHeader('Accept'));
    }
    return $this->_responseFormat;
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
}
?>
