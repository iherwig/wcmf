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
namespace wcmf\application\controller;

use Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;
use wcmf\lib\util\StringUtil;

/**
 * MultipleActionController executes multiple actions by passing them do the
 * appropriate controllers and returning all results as once.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Execute the given actions.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `data`            | An associative array with unique/sortable keys and values that describe an action to perform
 * | _out_ `data`           | An associative array with the same keys and values that describe the resonse of each action
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * The data array may contain the following special variables, that will be replaced by the described values:
 * - `{last_created_oid:type}`  will be replaced by the oid lastly created object of the given type
 *
 * An example of input data in JSON:
 * @code
    data: {
      action1: {
        action: "create",
        params: {
          oid: "Author:wcmffb298f3784dd49548a05d43d7bf88590",
          name: "Ingo Herwig"
        }
      },
      action2: {
        action: "read",
        params: {
          oid: "{last_created_oid:Author}"
        }
      }
    }
 * @endcode
 *
 * The output data for the preceding request could look like
 * @code
   data: {
     action1: {
       oid: "Author:123",
       ...
     },
     action2: {
       object: {
         oid: "Author:123",
         modified: "2001-01-01 01:01",
         creator: "admin"
         ...
       }
     }
   }
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class MultipleActionController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    // check if we have an array of arrays
    $request = $this->getRequest();
    $response = $this->getResponse();
    if ($request->hasValue('data')) {
      $data = $request->getValue('data');
      foreach($data as $key => $value) {
        if (!is_array($value)) {
          $response->addError(ApplicationError::get('PARAMETER_INVALID',
            array('invalidParameters' => array('data.'.$key))));
          return false;
        }
      }
    }
    else {
      $response->addError(ApplicationError::get('PARAMETER_MISSING',
        array('missingParameters' => array('data'))));
      return false;
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {
    // create and execute requests for the actions given in data
    $request = $this->getRequest();
    $response = $this->getResponse();
    $results = array();
    $data = $request->getValue('data');
    $actions = array_keys($data);
    $numActions = sizeof($actions);
    $exceptions = array();
    $actionMapper = ObjectFactory::getInstance('actionMapper');

    $formats = ObjectFactory::getInstance('formats');
    $nullFormat = $formats['null'];

    for($i=0; $i<$numActions; $i++) {
      $actionId = $actions[$i];
      if (Log::isDebugEnabled(__CLASS__)) {
        Log::debug("processing action: ".$actionId.":\n".StringUtil::getDump($data[$actionId]), __CLASS__);
      }
      // replace special variables
      $this->replaceVariables($data[$actionId]);

      // create the request
      $actionData = $data[$actionId];
      $context = isset($actionData['context']) ? $actionData['context'] : '';
      $action = isset($actionData['action']) ? $actionData['action'] : '';
      $params = isset($actionData['params']) ? $actionData['params'] : array();
      $requestPart = new Request('', $context, $action);
      $requestPart->setValues($params);
      $requestPart->setFormat($nullFormat);
      $requestPart->setResponseFormat($nullFormat);

      // execute the request
      try {
        $responsePart = $actionMapper->processAction($requestPart);
      }
      catch (Exception $ex) {
        Log::error($ex->__toString(), __CLASS__);
        $exceptions[] = $ex;
      }

      // collect the result
      $results[$actionId] = $responsePart->getValues();
    }
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($results, __CLASS__);
    }
    // add error from first exception to mark the action set execution as failed
    if (sizeof($exceptions) > 0) {
      $ex = $exceptions[0];
      $response->setValue('success', false);
      $response->setValue('errorCode', $ex->getCode());
      $response->setValue('errorMessage', $ex->getMessage());
    }
    $response->setValue('data', $results);
    $response->setAction('ok');
  }

  /**
   * Check the given data array for special variables to replace
   * Variables have either the form 'variable_name' or 'variable_name:column_separated_parameters'
   * @param $data A reference to the associative data array
   */
  private function replaceVariables(&$data) {
    $keys = array_keys($data);
    for($i=0; $i<sizeof($keys); $i++) {
      $key = $keys[$i];
      $value = $data[$key];

      // replace variables
      $newKey = $this->replaceVariablesString($key);
      $newValue = $this->replaceVariablesString($value);

      // replace entry
      if ($key != $newKey || $value != $newValue) {
        if (Log::isDebugEnabled(__CLASS__)) {
          if ($key != $newKey) {
            Log::debug("Replace $key by $newKey", __CLASS__);
          }
          if ($value != $newValue) {
            Log::debug("Replace $value by $newValue", __CLASS__);
          }
        }
        unset($data[$key]);
        $data[$newKey] = $newValue;
      }
    }
  }

  /**
   * Check the given string for special variables to replace
   * Variables have either the form 'variable_name' or 'variable_name:column_separated_parameters'
   * @param $value The string
   * @return String
   */
  private function replaceVariablesString($value) {
    if (!is_string($value)) {
      return $value;
    }
    preg_match_all('/\{([^\{]+)\}/', $value, $variableMatches);
    $variables = $variableMatches[1];
    foreach($variables as $variable) {
      preg_match('/^([^:]+)[:]*(.*)$/', $variable, $matches);
      if (sizeof($matches > 0)) {
        $variableName = $matches[1];
        $parameters = $matches[2];
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

        // last_created_oid
        if ($variableName == 'last_created_oid') {
          $type = $parameters;
          if ($persistenceFacade->isKnownType($type)) {
            $oid = $persistenceFacade->getLastCreatedOID($type);
            $value = preg_replace("/{".$variable."}/", $oid, $value);
          }
        }

        // oid reference
        if ($persistenceFacade->isKnownType($variableName)) {
          $type = $variableName;
          $oid = $persistenceFacade->getLastCreatedOID($type);
          $value = $oid;
        }
      }
    }
    return $value;
  }
}
?>

