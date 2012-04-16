<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005 wemove digital solutions GmbH
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */
namespace wcmf\application\controller;

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\Controller;
use wcmf\lib\presentation\Request;

/**
 * MultipleActionController is a controller that executes multiple actions by
 * passing them do the appropriate controllers and returning all results as once.
 *
 * <b>Input actions:</b>
 * - unspecified: Execute the given actions
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] data An associative array with unique/sortable keys and values that describe an action to perform
 * @param[out] data An associative array with the same keys and values that describe the resonse of each action
 *
 * The data array may contain the following special variables, that will be replaced by the described values:
 * - {last_created_oid:type}  will be replaced by the oid lastly created object of the given type
 *
 * An example of input data in JSON:
 * @code
    data: {
      action1: {
        action: "new",
        type: "ChiGoal"
      }
      action2: {
        action: "display",
        oid: {last_created_oid:ChiGoal},
        omitMetaData: true
      }
    }
 * @endcode
 *
 * The output data for the preceding request could look like
 * @code
   data: {
     action1: {
       oid: "ChiGoal:123",
       success: "1"
     }
     action2: {
       oid: "ChiGoal:123",
       node: {
         "0": {
           modified: "2001-01-01 01:01",
           creator: "admin"
           ...
         }
         ...
       },
       success: "1"
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
    $data = &$this->_request->getValue('data');
    foreach($data as $key => $value) {
      if (!is_array($value)) {
        $this->setErrorMsg("Data should be an associative array of action definition arrays.");
        return false;
      }
    }
    return true;
  }

  /**
   * @see Controller::hasView()
   */
  public function hasView() {
    return false;
  }

  /**
   * (Dis-)Associate the Nodes.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    // create and execute requests for the actions given in data
    $results = array();
    $data = &$this->_request->getValue('data');
    $actions = array_keys($data);
    $numActions = sizeof($actions);
    for($i=0; $i<$numActions; $i++)
    {
      $action = $actions[$i];
      $GLOBALS['gJSONData'] = array();

      if (Log::isDebugEnabled(__CLASS__))
        Log::debug("processing action: ".$action.":\n".StringUtil::getDump($data[$action]), __CLASS__);

      // replace special variables
      $this->replaceVariables($data[$action]);

      // for all requests we choose TerminateController as source controller
      // to make sure that we process the action and return to here

      // create the request
      $request = new Request(
        'TerminateController',
        $data[$action]['context'],
        $data[$action]['action'],
        $data[$action]
      );
      $request->setFormat($this->_request->getFormat());
      $request->setResponseFormat($this->_request->getResponseFormat());

      // execute the request
      $response = ActionMapper::processAction($request);

      // collect the result
      $results[$action] = &$response->getValues();
    }
    if (Log::isDebugEnabled(__CLASS__))
      Log::debug($results, __CLASS__);

    $this->_response->setValue('data', $results);
    $this->_response->setAction('ok');
    return true;
  }

  /**
   * Check the given data array for special variables to replace
   * Variables have either the form 'variable_name' or 'variable_name:column_separated_parameters'
   * @param data A reference to the associative data array
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
          if ($key != $newKey)
            Log::debug("Replace $key by $newKey", __CLASS__);
          if ($value != $newValue)
            Log::debug("Replace $value by $newValue", __CLASS__);
        }
        unset($data[$key]);
        $data[$newKey] = $newValue;
      }
    }
  }

  /**
   * Check the given string for special variables to replace
   * Variables have either the form 'variable_name' or 'variable_name:column_separated_parameters'
   * @param value The string
   * @return The string
   */
  private function replaceVariablesString($value) {
    preg_match_all('/\{([^\{]+)\}/', $value, $variableMatches);
    $variables = $variableMatches[1];
    foreach($variables as $variable) {
      preg_match('/^([^:]+)[:]*(.*)$/', $variable, $matches);
      if (sizeof($matches > 0)) {
        $variableName = $matches[1];
        $parameters = $matches[2];

        // last_created_oid
        if ($variableName == 'last_created_oid') {
          $type = $parameters;
          if (ObjectFactory::getInstance('persistenceFacade')->isKnownType($type)) {
            $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
            $oid = $persistenceFacade->getLastCreatedOID($type);
            $value = preg_replace("/{".$variable."}/", $oid, $value);
          }
        }

        // Dionysos oid reference
        if (ObjectFactory::getInstance('persistenceFacade')->isKnownType($variableName)) {
          $type = $variableName;
          $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
          $oid = $persistenceFacade->getLastCreatedOID($type);
          $value = $oid;
        }
      }
    }
    return $value;
  }
}
?>

