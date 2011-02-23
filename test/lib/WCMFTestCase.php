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
 * $Id: WCMFTestCase.php 998 2009-05-29 01:29:20Z iherwig $
 */
require_once(WCMF_BASE."wcmf/lib/util/class.InifileParser.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Request.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Application.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.ActionMapper.php");

/**
 * @class WCMFTestCase
 * @ingroup test
 * @brief WCMFTestCase is a PHPUnit test case, that
 * serves as base class for wCMF test cases.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class WCMFTestCase extends PHPUnit_Framework_TestCase
{
  /**
   * Turn authorization validation on/off.
   * @param True/False wether to turn it off or on
   */
  protected function runAnonymous($isAnonymous)
  {
    $parser = InifileParser::getInstance();
    $parser->setValue('anonymous', $isAnonymous, 'cms');
  }

  /**
   * Process a request as if it was sent to main.php
   * @param request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction call)
   */
  protected function simulateRequest($request)
  {
    // set formatter
    $request->setFormat('Null');
    $request->setResponseFormat('Null');

    // initialize the application
    $application = Application::getInstance();
    $callParams = $application->initialize('../application/include/');

    // reset the action mapper, because otherwise all requests would be cumulated
    ActionMapper::reset();
    $response = ActionMapper::processAction($request);
    return $response;
  }

  /**
   * Start a session. This is usefull for simulateRequest calls
   * @param login The login of the user
   * @param password The password of the user
   * @return The session id. Use this as data['sid'] parameter for
   *    subsequent simulateRequest calls
   */
  protected function startSession($login, $password)
  {
    $request = new Request('LoginController',
      '',
      'dologin',
      array(
        'login' => 'admin',
        'password' => 'admin'
      )
    );
    $response = $this->simulateRequest($request);
    return $response->getValue('sid');
  }

  /**
   * End a session.
   * @param sid The session id
   */
  protected function endSession($sid)
  {
    $request = new Request('',
      '',
      'logout',
      array(
        'sid' => $sid
      )
    );
    $response = $this->simulateRequest($request);
  }

  /**
   * Create a test object with the given oid and attributes. A test object
   * with the same object id will be deleted first.
   * @param oid The object id
   * @param attribute An associative array with the value names as keys
   *    and the values as values
   * @return The object
   */
  protected function createTestObject(ObjectId $oid, array $attributes)
  {
    $this->deleteTestObject($oid);

    $persistenceFacade = PersistenceFacade::getInstance();
    $testObj = $persistenceFacade->create($oid->getType(), BUILDDEPTH_SINGLE);
    $mapper = $testObj->getMapper();
    $i = 0;
    $ids = $oid->getId();
    foreach ($mapper->getPkNames() as $pkName) {
      $testObj->setValue($pkName, $ids[$i++]);
    }
    foreach ($attributes as $name => $value) {
      $testObj->setValue($name, $value);
    }
    $testObj->save();
    return $testObj;
  }

  /**
   * Load a test object
   * @param oid The object id
   * @return The object
   */
  protected function loadTestObject(ObjectId $oid)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $object = $persistenceFacade->load($oid, BUILDDEPTH_SINGLE);
    return $object;
  }

  /**
   * Delete a test object
   * @param oid The object id
   */
  protected function deleteTestObject(ObjectId $oid)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $persistenceFacade->delete($oid);
  }

  /**
   * Set a configuration value
   * @see InifileParser::setValue()
   */
  protected function setConfigValue($key, $value, $section)
  {
    $parser = InifileParser::getInstance();
    $parser->setValue($key, $value, $section);
  }

  /**
   * Call a protected/private method of an instance (PHP >= 5.3.2)
   * @param instance The instance
   * @param methodName The method name
   * @param args An array of method arguments
   */
  protected function callProtectedMethod($instance, $methodName, $args=null)
  {
    $className = get_class($instance);
    $class = new ReflectionClass($className);
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
  protected function enableProfiler($type)
  {
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
  protected function printProfile($type)
  {
    $mapper = PersistenceFacade::getInstance()->getMapper($type);
    $profiler = $mapper->getProfiler();

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