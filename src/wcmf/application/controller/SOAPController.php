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

use wcmf\lib\presentation\Controller;
use wcmf\lib\service\SoapServer;

/**
 * Global server instance used by the generated soap interface
 */
$server = null;

/**
 * SOAPController is a controller that handles SOAP requests.
 *
 * <b>Input actions:</b>
 * - unspecified: Handle action according to soap request
 *
 * <b>Output actions:</b>
 * - depends on the controller, to that the action is delegated
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SOAPController extends Controller {

  /**
   * Execute the requested SOAP action
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    global $server;

    // instantiate server
    $server = new SoapServer();

    // register search method
    $server->register('wcmf\application\controller\SOAPController.search',
      array('query' => 'xsd:string'), array('return' => 'tns:SearchResultList'),
      $server::TNS, $server->wsdl->endpoint.'#search', 'document', 'literal'
    );

    // include the generated interface
    require("soap-interface.php");

    // invoke the service
    if (!isset($HTTP_RAW_POST_DATA)) {
      $HTTP_RAW_POST_DATA = implode("\r\n", file('php://input'));
    }
    $server->service($HTTP_RAW_POST_DATA);
    exit;
  }

  /**
   * Search
   * @param query The search term
   * @return Array of SearchResultItem on success
   */
  public static function search($query) {
    global $server;
    $response = $server->doCall('search', array('query' => $query));
    $result = array();
    foreach ($response->getValue('list') as $item) {
      $result[] = array('type' => $item['type'], 'oid' => $item['oid'],
        'displayValue' => $item['displayValue'], 'summary' => $item['summary']
      );
    }
    return array('return' => $result);
  }
}
?>
