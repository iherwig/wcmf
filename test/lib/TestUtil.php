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
namespace test\lib;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;

/**
 * TestUtil provides helper methods for testing wCMF functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TestUtil {

  /**
   * Turn authorization validation on/off.
   * @param True/False wether to turn it off or on
   */
  public static function runAnonymous($isAnonymous) {
    $config = ObjectFactory::getConfigurationInstance();
    $config->setValue('anonymous', $isAnonymous, 'application');
  }

  /**
   * Process a request as if it was sent to main.php
   * @param request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction() call)
   */
  public static function simulateRequest($request) {
    // set formatter
    $request->setFormat('null');
    $request->setResponseFormat('null');

    // reset the action mapper, because otherwise all requests would be cumulated
    $actionMapper = ObjectFactory::getInstance('actionMapper');
    $actionMapper->reset();
    $response = $actionMapper->processAction($request);
    return $response;
  }

  /**
   * Start a session. This is usefull for simulateRequest calls
   * @param user The name of the user
   * @param password The password of the user
   * @return The session id. Use this as data['sid'] parameter for
   *    subsequent simulateRequest calls
   */
  public static function startSession($user, $password) {
    $session = ObjectFactory::getInstance('session');
    $authUser = ObjectFactory::getInstance('authUser');
    $success = $authUser->login($user, $password, false);
    if ($success) {
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $session->clear();
      $session->set($permissionManager->getAuthUserVarname(), $authUser);
    }
    return $session->getID();
  }

  /**
   * End a session.
   */
  public static function endSession() {
    $session = ObjectFactory::getInstance('session');
    $session->destroy();
  }

  /**
   * Set a configuration value
   * @see Configuration::setValue()
   */
  public static function setConfigValue($key, $value, $section) {
    $config = ObjectFactory::getConfigurationInstance();
    $config->setValue($key, $value, $section);
  }

  /**
   * Call a protected/private method of an instance (PHP >= 5.3.2)
   * @param instance The instance
   * @param methodName The method name
   * @param args An array of method arguments
   */
  public static function callProtectedMethod($instance, $methodName, $args=null) {
    $className = get_class($instance);
    $class = new \ReflectionClass($className);
    $method = $class->getMethod($methodName);
    $method->setAccessible(true);

    if ($args != null) {
      return $method->invokeArgs($instance, $args);
    }
    else {
      return $method->invoke($instance);
    }
  }

  public static function getSizeof($var) {
      $startMemory = memory_get_usage();
      $var = unserialize(serialize($var));
      return memory_get_usage() - $startMemory - PHP_INT_SIZE * 8;
  }

  /**
   * Enable the Zend_Db_Profiler for a given entity type.
   * @param type The entity type
   */
  public static function enableProfiler($type) {
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($type);
    if ($mapper instanceof RDBMapper) {
      $mapper->enableProfiler();
    }
  }

  /**
   * Print the profile of the operations on a given entity type.
   * The profiler must have been enabled first
   * @param type The entity type
   */
  public static function printProfile($type) {
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($type);
    $profiler = $mapper->getProfiler();

    echo "\n";
    foreach ($profiler->getQueryProfiles() as $query) {
      echo $query->getElapsedSecs()."s: ".$query->getQuery()."\n";
    }

    $totalTime = $profiler->getTotalElapsedSecs();
    $queryCount = $profiler->getTotalNumQueries();
    $longestTime = 0;
    $longestQuery = null;
    foreach ($profiler->getQueryProfiles() as $query) {
      if ($query->getElapsedSecs() > $longestTime) {
        $longestTime  = $query->getElapsedSecs();
        $longestQuery = $query->getQuery();
      }
    }
    echo "\n";
    echo 'Executed '.$queryCount.' queries in '.$totalTime.' seconds'."\n";
    echo 'Average query length: '.$totalTime/$queryCount.' seconds'."\n";
    echo 'Queries per second: '.$queryCount/$totalTime."\n";
    echo 'Longest query length: '.$longestTime."\n";
    echo "Longest query: \n".$longestQuery."\n";
  }
}
?>