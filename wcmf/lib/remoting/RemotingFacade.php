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
namespace wcmf\lib\remoting;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Response;
use wcmf\lib\remoting\HTTPClient;
use wcmf\lib\remoting\RPCClient;

/**
 * RemotingFacade is used to communicate with other wCMF instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemotingFacade {

  private static $_instance = null;
  private $_clients = array();
  private $_users = array();

  private function __construct() {}

  /**
   * Get the singleton instance.
   * @return A reference to a RemotingFacade instance
   */
  public static function getInstance() {
    if (!isset(self::$_instance)) {
      self::$_instance = new RightsManager();
    }
    return self::$_instance;
  }

  /**
   * Send a request to the server identified by serverKey.
   * @param serverKey An entry in the configuration section 'remoteserver'
   * @param request A Request instance
   * @return A Response instance
   */
  public function doCall($serverKey, $request) {
    $client = $this->getClient($serverKey);
    if ($client) {
      $response = $client->call($request);
      return $response;
    }
    return new Response();
  }

  /**
   * Get a client instance for a given server key
   * @param serverKey An entry in the configuration section 'remoteserver'
   * @return A client instance or null
   */
  private function getClient($serverKey) {
    if (!isset($this->_clients[$serverKey])) {
      $config = ObjectFactory::getConfigurationInstance();
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
      $this->_clients[$serverKey] = $client;
    }
    return $this->_clients[$serverKey];
  }

  /**
   * Get the remote user name for a given server key
   * @param serverKey An entry in the configuration section 'remoteuser'
   * @return A user instance
   */
  private function getRemoteUser($serverKey) {
    if (!isset($this->_users[$serverKey])) {
      $config = ObjectFactory::getConfigurationInstance();
      $remoteLogin = $config->getValue($serverKey, 'remoteuser');
      $objectFactory = ObjectFactory::getInstance();
      $userManager = $objectFactory->getInstance('userManager');
      $user = $userManager->getUser($remoteLogin);
      if ($user != null) {
        $this->_users[$serverKey] = $user;
      }
      else {
        throw new RuntimeException("The remote user with login '".$remoteLogin."' does not exist.\n");
      }
    }
    return $this->_users[$serverKey];
  }
}
?>
