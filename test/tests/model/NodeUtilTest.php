<?php
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.ObjectId.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."test/lib/WCMFTestCase.php");

class NodeUtilTest extends WCMFTestCase
{
  public function testGetConnection()
  {
    $this->runAnonymous(true);

    $paths = NodeUtil::getConnections('Author', null, 'Image', 'child');
    $this->assertTrue(sizeof($paths) == 2);
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertTrue(in_array('TitleImage', $endRoles));
    $this->assertTrue(in_array('NormalImage', $endRoles));

    $paths = NodeUtil::getConnections('Author', null, 'Image', 'all');
    $this->assertTrue(sizeof($paths) == 2);
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertTrue(in_array('TitleImage', $endRoles));
    $this->assertTrue(in_array('NormalImage', $endRoles));

    $paths = NodeUtil::getConnections('Image', 'Author', null, 'parent');
    $this->assertTrue(sizeof($paths) == 2);
    $startRoles = array($paths[0]->getStartRole(), $paths[1]->getStartRole());
    $this->assertTrue(in_array('TitleImage', $startRoles));
    $this->assertTrue(in_array('NormalImage', $startRoles));

    $paths = NodeUtil::getConnections('Page', null, 'Page', 'all');
    $this->assertTrue(sizeof($paths) == 2);
    $endRoles = array($paths[0]->getEndRole(), $paths[1]->getEndRole());
    $this->assertTrue(in_array('ChildPage', $endRoles));
    $this->assertTrue(in_array('ParentPage', $endRoles));

    $paths = NodeUtil::getConnections('Page', null, 'Page', 'parent');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'ParentPage');

    $paths = NodeUtil::getConnections('Page', 'ParentPage', null, 'parent');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'ParentPage');

    $paths = NodeUtil::getConnections('Page', 'ChildPage', null, 'child');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'ChildPage');

    $paths = NodeUtil::getConnections('Page', 'ChildPage', null, 'parent');
    $this->assertTrue(sizeof($paths) == 0);

    $paths = NodeUtil::getConnections('Page', 'Author', null, 'parent');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'Author');

    $paths = NodeUtil::getConnections('Document', null, 'Page', 'child');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'Page');

    $paths = NodeUtil::getConnections('Page', null, 'Document', 'child');
    $this->assertTrue(sizeof($paths) == 1);
    $this->assertTrue($paths[0]->getEndRole() == 'Document');

    $this->runAnonymous(false);
  }
}
?>