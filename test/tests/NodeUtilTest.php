<?php
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");

class NodeUtilTest extends WCMFTestCase
{
  public function testGetPath()
  {
    $this->assertTrue(true);
    $this->runAnonymous(true);
    $persistenceFacade = PersistenceFacade::getInstance();

    $nm = $persistenceFacade->load(new ObjectId('NMUserRole', array(3,2)), BUILDDEPTH_SINGLE);
    $path = NodeUtil::getPath($nm);

    $this->assertTrue(sizeof($path) == 1, "The path length is 1");

    $this->runAnonymous(false);
  }

  public function testGetConnection()
  {
    $this->runAnonymous(true);
    $relations = NodeUtil::getConnection('Image', 'Author', 'parent');
    $this->assertTrue(sizeof($relations) == 2, "The path length is 2");

    $relations = NodeUtil::getConnection('Page', 'Page', 'parent');
    $this->assertTrue(sizeof($relations) == 1, "The path length is 1");

    $relations = NodeUtil::getConnection('Page', 'Author', 'parent');
    $this->assertTrue(sizeof($relations) == 1, "The path length is 1");

    $relations = NodeUtil::getConnection('Author', 'Image', 'child');
    $this->assertTrue(sizeof($relations) == 2, "The path length is 2");

    $relations = NodeUtil::getConnection('Document', 'Page', 'child');
    $this->assertTrue(sizeof($relations) == 1, "The path length is 1");

    $relations = NodeUtil::getConnection('Page', 'Document', 'child');
    $this->assertTrue(sizeof($relations) == 1, "The path length is 1");

    $relations = NodeUtil::getConnection('Author', 'Image', 'all');
    $this->assertTrue(sizeof($relations) == 2, "The path length is 2");

    $this->runAnonymous(false);
  }
}
?>