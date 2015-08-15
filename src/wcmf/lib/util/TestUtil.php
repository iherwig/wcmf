<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\util;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\impl\DefaultFactory;
use wcmf\lib\core\impl\MonologFileLogger;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBMapper;

/**
 * TestUtil provides helper methods for testing wCMF functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TestUtil {

  /**
   * Set up the wcmf framework. The method makes the following assumptions
   * about file locations:
   * - main configuration in $configPath.'config.ini'
   * - optional additional configuration in $configPath.'test.ini'
   * - logging configuration in $configPath.'log.ini'
   * @param $configPath The path to the configuration directory
   */
  public static function initFramework($configPath) {
    if (!file_exists($configPath) || is_file($configPath)) {
      throw new \Exception('Configuration path '.$configPath.' is not a directory. '.
              'Did you forget to generate code from the model?');
    }

    // setup logging
    $logger = new MonologFileLogger('main', $configPath.'log.ini');
    LogManager::configure($logger);

    // setup configuration
    $configuration = new InifileConfiguration($configPath);
    $configuration->addConfiguration('config.ini');
    $configuration->addConfiguration('test.ini');

    // setup object factory
    ObjectFactory::configure(new DefaultFactory($configuration));
    ObjectFactory::registerInstance('configuration', $configuration);

    $cache = ObjectFactory::getInstance('cache');
    $cache->clearAll();
  }

  /**
   * Create the test database, if sqlite is configured
   * @return Associative array with connection parameters and key 'connection'
   */
  public static function createDatabase() {
    // get connection from first entity type
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = $persistenceFacade->getKnownTypes();
    $mapper = $persistenceFacade->getMapper($types[0]);
    $pdo = $mapper->getConnection()->getConnection();

    // create sqlite db
    $params = $mapper->getConnectionParams();
    if ($params['dbType'] == 'sqlite') {
      $numTables = $pdo->query('SELECT count(*) FROM sqlite_master WHERE type = "table"')->fetchColumn();
      if ($numTables == 0) {
        $schema = file_get_contents(WCMF_BASE.'install/tables_sqlite.sql');
        $pdo->exec($schema);
      }
    }
    $params['connection'] = $pdo;
    return $params;
  }

  /**
   * Start the built-in webserver
   * @param $documentRoot Document root directory
   * @param $router Router script filename (optional)
   */
  public static function startServer($documentRoot, $router='') {
    if (!is_dir($documentRoot)) {
      throw new \Exception('Document root '.$documentRoot.' does not exist');
    }
    define('SERVER_HOST', 'localhost');
    define('SERVER_PORT', 8500);
    $cmd = sprintf('php -S %s:%d -t %s %s', SERVER_HOST, SERVER_PORT, $documentRoot, $router);

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
      throw new \Exception("Failed to execute ".$cmd);
    }

    // kill the web server when the process ends
    register_shutdown_function(function() use ($resource) {
      $status = proc_get_status($resource);
      $pid = $status['pid'];
      if (TestUtil::isWindows()) {
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
   * Process a request as if it was sent to main.php
   * @param $request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction() call)
   */
  public static function simulateRequest($request) {
    // set formatter
    $request->setFormatByName('null');
    $request->setResponseFormatByName('null');

    // reset the action mapper, because otherwise all requests would be cumulated
    $actionMapper = ObjectFactory::getInstance('actionMapper');
    $actionMapper->reset();
    $response = $actionMapper->processAction($request);
    return $response;
  }

  /**
   * Start a session. This is usefull for simulateRequest calls
   * @param $user The name of the user
   * @param $password The password of the user
   * @return The session id. Use this as data['sid'] parameter for
   *    subsequent simulateRequest calls
   */
  public static function startSession($user, $password) {
    $session = ObjectFactory::getInstance('session');
    $authManager = ObjectFactory::getInstance('authenticationManager');
    $authUser = $authManager->login($user, $password);
    if ($authUser) {
      $session->clear();
      $session->setAuthUser($authUser);
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
    $config = ObjectFactory::getInstance('configuration');
    $config->setValue($key, $value, $section);
  }

  /**
   * Call a protected/private method of an instance (PHP >= 5.3.2)
   * @param $instance The instance
   * @param $methodName The method name
   * @param $args An array of method arguments
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
   * @param $type The entity type
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
   * @param $type The entity type
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