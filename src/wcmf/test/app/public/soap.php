<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(dirname(dirname(WCMF_BASE)))."/vendor/autoload.php");

use wcmf\lib\service\SoapServer;

// instantiate server
$server = new SoapServer();

// register search method
$server->register('search',
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

/**
 * Search
 * @param query The search term
 * @return Array of SearchResultItem on success
 */
function search($query) {
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
?>
