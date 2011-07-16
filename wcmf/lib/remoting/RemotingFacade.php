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
require_once(WCMF_BASE."wcmf/lib/util/Log.php");
require_once(WCMF_BASE."wcmf/lib/presentation/Request.php");
require_once(WCMF_BASE."wcmf/lib/presentation/Response.php");
require_once(WCMF_BASE."wcmf/lib/remoting/HTTPClient.php");
require_once(WCMF_BASE."wcmf/lib/remoting/RPCClient.php");
require_once(WCMF_BASE."wcmf/lib/util/ObjectFactory.php");

/**
 * @class RemotingFacade
 * @ingroup Remoting
 * @brief RemotingFacade is used to communicate with other wCMF instances.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RemotingFacade
{
  private $_clients = array();
  private $_users = array();
  
  /**
   * Get the singleton instance.
   * @return A reference to a RemotingFacade instance
   */
  function &getInstance()
  {
    static $instance = null;

    if (!isset($instance))
      $instance = new RemotingFacade();

    return $instance;
  }
  /**
   * Send a request to the server identified by serverKey.
   * @param serverKey An entry in the configuration section 'remoteserver'
   * @param request A Request instance
   * @return A Response instance
   */
  function doCall($serverKey, $request)
  {
    $client = $this->getClient($serverKey);
    if ($client)
    {
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
  private function getClient($serverKey)
  {
    if (!isset($this->_clients[$serverKey]))
    {
      $parser = &InifileParser::getInstance();
      if (($serverDef = $parser->getValue($serverKey, 'remoteserver')) !== false)
      {
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
      else
      {
        WCMFException::throwEx("The remote server with key '".$serverKey."' is unknown.\n".
          $parser->getErrorMsg(), __FILE__, __LINE__);
        return null;
      }
    }
    return $this->_clients[$serverKey];
  }
  /**
   * Get the remote user name for a given server key
   * @param serverKey An entry in the configuration section 'remoteuser'
   * @return A user instance
   */
  private function getRemoteUser($serverKey)
  {
    if (!isset($this->_users[$serverKey]))
    {
      $parser = &InifileParser::getInstance();
      if (($remoteLogin = $parser->getValue($serverKey, 'remoteuser')) !== false)
      {
        $objectFactory = &ObjectFactory::getInstance();
        $userManager = &$objectFactory->createInstanceFromConfig('implementation', 'UserManager');
        $user = &$userManager->getUser($remoteLogin);
        if ($user != null) {
          $this->_users[$serverKey] = $user;
        }
        else
        {
          WCMFException::throwEx("The remote user with login '".$remoteLogin."' does not exist.\n", __FILE__, __LINE__);
          return null;
        }
      }
      else
      {
        WCMFException::throwEx("The remote user with key '".$serverKey."' is unknown.\n".
          $parser->getErrorMsg(), __FILE__, __LINE__);
        return null;
      }
    }
    return $this->_users[$serverKey];
  }
}
?>
