<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\service;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\service\impl\HTTPClient;
use wcmf\lib\service\impl\RPCClient;

/**
 * RemotingServer is used to communicate with other wCMF instances.
 * The url and login credentials of a remote instance are configured
 * using the configuration sections RemoteServer and RemoteUser.
 * Each remote instance is identified by a unique server key. The following
 * example configures ServerKeyA over http and ServerKeyB over command
 * line:
 *
 * @code
 * [remoteserver]
 * ServerKeyA = http://localhost/wcmfA
 * ServerKeyB = /path/to/wcmfB/main.php
 *
 * [remoteuser]
 * ServerKeyA = {loginA, passwordA}
 * ServerKeyB = {loginB, passwordB}
 *
 * [system]
 * php = /path/to/php-cli
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemotingServer {

  private $clients = [];
  private $users = [];

  /**
   * Send a request to the server identified by serverKey.
   * @param $serverKey An entry in the configuration section 'remoteserver'
   * @param $request A Request instance
   * @return A Response instance
   */
  public function doCall($serverKey, $request) {
    $client = $this->getClient($serverKey);
    if ($client) {
      $response = $client->call($request);
      return $response;
    }
    return ObjectFactory::getNewInstance('response');
  }

  /**
   * Get a client instance for a given server key
   * @param $serverKey An entry in the configuration section 'remoteserver'
   * @return A client instance or null
   */
  private function getClient($serverKey) {
    if (!isset($this->clients[$serverKey])) {
      $config = ObjectFactory::getInstance('configuration');
      $serverDef = $config->getValue($serverKey, 'remoteserver');
      // get remote the user
      $user = $this->getRemoteUser($serverKey);

      $client = null;
      if (strpos($serverDef, 'http://') === 0 || strpos($serverDef, 'https://') === 0) {
        $client = new HTTPClient($serverDef, $user);
      }
      else {
        $client = new RPCClient($serverDef, $user);
      }
      $this->clients[$serverKey] = $client;
    }
    return $this->clients[$serverKey];
  }

  /**
   * Get the remote user login and password for a given server key
   * @param $serverKey An entry in the configuration section 'remoteuser'
   * @return Array with keys 'login', 'password'
   */
  private function getRemoteUser($serverKey) {
    if (!isset($this->users[$serverKey])) {
      $config = ObjectFactory::getInstance('configuration');
      $remoteUser = $config->getValue($serverKey, 'remoteuser');
      if (is_array($remoteUser) && sizeof($remoteUser) == 2) {
        $this->users[$serverKey] = [
          'login' => $remoteUser[0],
          'password' => $remoteUser[1]
        ];
      }
      else {
        throw new IllegialConfigurationException(
                "Remote user definition of '".$serverKey.
                "' must be an array of login and password."
        );
      }
    }
    return $this->users[$serverKey];
  }
}
?>
