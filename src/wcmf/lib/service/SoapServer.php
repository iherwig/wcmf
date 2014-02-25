<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
namespace wcmf\lib\service;

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Application;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

require_once(WCMF_BASE."wcmf/vendor/nusoap/lib/nusoap.php");

/**
 * @class SoapServer
 * @ingroup Presentation
 * @brief SoapServer extends nusoap server to actually process
 * requests inside the application context.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapServer extends \nusoap_server {

  const TNS = 'http://wcmf.sourceforge.net';

  private $_application = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->configureWSDL('SOAPService', self::TNS, false, 'document');
    $this->wsdl->schemaTargetNamespace = self::TNS;

    // register default complex types
    $this->wsdl->addComplexType(
        'OidList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
          array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:string[]')
        ),
        'xsd:string'
    );

    $this->wsdl->addComplexType(
        'SearchResultList',
        'complexType',
        'array',
        '',
        'SOAP-ENC:Array',
        array(),
        array(
            array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:SearchResultItem[]')
        ),
        'tns:SearchResultItem'
    );

    $this->wsdl->addComplexType('SearchResultItem', 'complexType', 'struct', 'sequence', '',
        array(
            'type' => array('name' => 'type', 'type' => 'xsd:string'),
            'oid' => array('name' => 'oid', 'type' => 'xsd:string'),
            'displayValue' => array('name' => 'displayValue', 'type' => 'xsd:string'),
            'summary' => array('name' => 'summary', 'type' => 'xsd:string')
        )
    );

    // initialize application
    $this->_application = new Application();
    try {
      $this->_application->initialize();
    }
    catch (Exception $ex) {
      $this->handleException($ex);
    }
  }

  /**
   * @see nusoap_server::service
   */
  public function service($data) {
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($data, __CLASS__);
    }
    try {
      parent::service($data);
    }
    catch (Exception $ex) {
      $this->handleException($ex);
    }
  }

  /**
   * Get a dummy object id to be used in a request
   * @param $type The entity type
   * @return ObjectId
   */
  public function getDummyOid($type) {
    return new ObjectId($type);
  }

  /**
   * Process a soap call
   * @param action The action
   * @param params The action parameters
   * @return The Response instance from the executed Controller
   */
  public function doCall($action, $params) {
    $authHeader = $this->requestHeader['Security']['UsernameToken'];
    $formats = ObjectFactory::getInstance('formats');

    $request = new Request('', '', 'actionSet');
    $request->setResponseFormat($formats['null']);
    $request->setValues(array(
      'data' => array(
        'action1' => array(
          'action' => 'login',
          'user' => $authHeader['Username'],
          'password' => $authHeader['Password']['!']
        ),
        'action2' => array_merge(array('action' => $action), $params),
        'action3' => array(
          'action' => 'logout'
        )
      )
    ));

    // run the application
    $response = $this->_application->run($request);
    if ($response->hasErrors()) {
      $errors = $response->getErrors();
      throw new ApplicationException($request, $response, $errors[0]);
    }
    $responseData = $response->getValue('data');
    $data = $responseData['action2'];
    $actionResponse = new Response($data['controller'], $data['context'], $data['action']);
    $actionResponse->setFormat($formats['soap']);
    $actionResponse->setValues($data);
    \wcmf\lib\presentation\format\Formatter::serialize($actionResponse);
    return $actionResponse;
  }

  /**
   * Handle an exception
   * @param $ex
   */
  private function handleException($ex) {
    Log::error($ex->getMessage()."\n".$ex->getTraceAsString(), __CLASS__);
    $this->fault('SOAP-ENV:SERVER', $ex->getMessage(), '', '');
    $this->send_response();
  }
}
?>
