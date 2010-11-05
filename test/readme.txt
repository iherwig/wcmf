
wCMF Unit Tests
===============

To execute the tests run AllTests.php in the browser.

All tests in the 'tests' directory are run automatically, if the following
conditions are met:

1. The filename ends with 'Test.php'
2. The file defines a class whose name is similar to the filename except
   for the trailing '.php'
3. The class extends PHPUnit_Framework_TestCase
4. The test method names start with 'test'
5. The filename does not correspond to a line in the file 'ignore.txt'

To ignore a test, put the corresponding filename into one line
of a file named 'ignore.txt' in the tests directory.

---------------

An example test may look like this (PersistenceTest.php)


<?php

require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");

class PersistenceTest extends WCMFTestCase
{
  public function testLoad()
  {    
    $this->assertContains('UserRDB', PersistenceFacade::getInstance()->getKnownTypes(), "UserRDB is a known type.");
    
    $persistenceFacade = PersistenceFacade::getInstance();
    $user = $persistenceFacade->loadFirstObject('UserRDB', BUILDDEPTH_SINGLE);
    if ($user != null)
      $this->assertTrue($user->getType() == 'UserRDB', "The loaded user has type UserRDB");
  }
}

?>