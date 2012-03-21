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

use wcmf\lib\config\InifileParser;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\Application;
use wcmf\lib\presentation\Request;

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
    $parser = InifileParser::getInstance();
    $parser->setValue('anonymous', $isAnonymous, 'cms');
  }

  /**
   * Process a request as if it was sent to main.php
   * @param request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction call)
   */
  public static function simulateRequest($request) {
    // set formatter
    $request->setFormat('Null');
    $request->setResponseFormat('Null');

    // initialize the application
    $application = Application::getInstance();
    $application->initialize('../application/include/');

    // reset the action mapper, because otherwise all requests would be cumulated
    ActionMapper::reset();
    $response = ActionMapper::processAction($request);
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
    $request = new Request('LoginController',
      '',
      'dologin'
    );
    $request->setValues(array(
        'user' => $user,
        'password' => $password
    ));
    $response = self::simulateRequest($request);
    return $response->getValue('sid');
  }

  /**
   * End a session.
   * @param sid The session id
   */
  public static function endSession($sid) {
    $request = new Request('',
      '',
      'logout'
    );
    $request->setValues(array(
        'sid' => $sid
    ));
    self::simulateRequest($request);
  }

  /**
   * Create a test object with the given oid and attributes.
   * @param oid The object id
   * @param attribute An associative array with the value names as keys
   *    and the values as values
   * @return Node
   */
  public static function createTestObject(ObjectId $oid, array $attributes) {
    // check if the object already exists and delete it if necessary
    $persistenceFacade = PersistenceFacade::getInstance();
    $testObj = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
    if ($testObj) {
      // direct delete without transaction
      $testObj->getMapper()->delete($testObj);
    }

    $type = $oid->getType();
    $testObj = new $type($oid);
    foreach ($attributes as $name => $value) {
      $testObj->setValue($name, $value);
    }
    $testObj->setState(PersistentObject::STATE_NEW);
    PersistenceFacade::getInstance()->getTransaction()->registerNew($testObj);
    return $testObj;
  }

  /**
   * Load a test object
   * @param oid The object id
   * @return Node
   */
  public static function loadTestObject(ObjectId $oid) {
    $persistenceFacade = PersistenceFacade::getInstance();
    $object = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
    return $object;
  }

  /**
   * Delete a test object
   * @param oid ObjectId
   */
  public static function deleteTestObject(ObjectId $oid) {
    $persistenceFacade = PersistenceFacade::getInstance();
    $object = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
    if ($object) {
      $object->delete();
    }
  }

  /**
   * Set a configuration value
   * @see InifileParser::setValue()
   */
  public static function setConfigValue($key, $value, $section) {
    $parser = InifileParser::getInstance();
    $parser->setValue($key, $value, $section);
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

  /**
   * Enable the Zend_Db_Profiler for a given entity type.
   * @param type The entity type
   */
  public static function enableProfiler($type) {
    $mapper = PersistenceFacade::getInstance()->getMapper($type);
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
    $mapper = PersistenceFacade::getInstance()->getMapper($type);
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