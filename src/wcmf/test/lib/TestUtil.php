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
namespace wcmf\test\lib;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;
use wcmf\lib\security\impl\NullPermissionManager;

/**
 * TestUtil provides helper methods for testing wCMF functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TestUtil {

  private static $_nullPermissionManager = null;
  private static $_defaultPermissionManager = null;

  /**
   * Set up the wcmf framework
   */
  public static function initFramework() {
    ObjectFactory::clear();

    $configPath = WCMF_BASE.'app/config/';
    if (!file_exists($configPath)) {
      throw new Exception('Configuration path '.$configPath.' does not exist. '.
              'Did you forget to generate code from the model?');
    }

    Log::configure('log4php.php');

    $config = new InifileConfiguration($configPath);
    $config->addConfiguration('config.ini');
    ObjectFactory::configure($config);
    $cache = ObjectFactory::getInstance('cache');
    $cache->clearAll();
  }

  /**
   * Start the built-in webserver
   */
  public static function startServer() {
    define('SERVER_HOST', 'localhost');
    define('SERVER_PORT', 8500);
    $cmd = sprintf('php -S %s:%d -t %s', SERVER_HOST, SERVER_PORT, WCMF_BASE.'app/public');

    $descriptorspec = array(
      0 => array('pipe', 'r'), // stdin
      1 => array('pipe', 'w'), // stdout
      2 => array('pipe', 'a') // stderr
    );
    $pipes = null;
    if (self::isWindows()) {
      $resource = proc_open("start /B ".$cmd, $descriptorspec, $pipes);
    }
    else {
      $resource = proc_open("nohup ".$cmd, $descriptorspec, $pipes);
    }
    if (!is_resource($resource)) {
      exit("Failed to execute ".$cmd);
    }

    // kill the web server when the process ends
    register_shutdown_function(function() use ($resource) {
      $status = proc_get_status($resource);
      $pid = $status['pid'];
      if (self::isWindows()) {
        $output = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"$pid\"")));
        array_pop($output);
        $pid = end($output);
        exec("taskkill /F /T /PID $pid");
      }
      else {
        $pid = $pid+1;
        exec("kill -9 $pid");
      }
    });
  }

  /**
   * Turn authorization validation on/off.
   * @param Boolean whether to turn it off or on
   */
  public static function runAnonymous($isAnonymous) {
    if (!self::$_nullPermissionManager) {
      self::$_nullPermissionManager = new NullPermissionManager();
      self::$_defaultPermissionManager = ObjectFactory::getInstance('permissionManager');
    }
    $permissionManager = $isAnonymous ? self::$_nullPermissionManager : self::$_defaultPermissionManager;
    ObjectFactory::registerInstance('permissionManager', $permissionManager);
  }

  /**
   * Process a request as if it was sent to main.php
   * @param request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction() call)
   */
  public static function simulateRequest($request) {
    // set formatter
    $formats = ObjectFactory::getInstance('formats');
    $nullFormat = $formats['null'];
    $request->setFormat($nullFormat);
    $request->setResponseFormat($nullFormat);

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
    $authManager = ObjectFactory::getInstance('authenticationManager');
    $authUser = $authManager->login($user, $password);
    if ($authUser) {
      $session->clear();
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $permissionManager->setAuthUser($authUser);
    }
    else {
      throw new \RuntimeException("Session could not be started for user '$user'");
    }
    return $session->getID();
  }

  /**
   * End a session.
   */
  public static function endSession() {
    $session = ObjectFactory::getInstance('session');
    $session->destroy();
    $session->__destruct();
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

  public static function isWindows() {
    return (substr(php_uname(), 0, 7) == "Windows");
  }
}
?>