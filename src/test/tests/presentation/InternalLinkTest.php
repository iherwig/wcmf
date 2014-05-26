<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace test\tests\core;

use test\lib\BaseTestCase;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\InternalLink;

/**
 * InternalLinkTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class InternalLinkTest extends BaseTestCase {

  public function testMakeLink() {
    $link = InternalLink::makeLink(new ObjectId("Publisher", array(12)));
    $this->assertEquals("link://app.src.model.Publisher:12", $link);
  }

  public function testMakeAnchorLink() {
    $link = InternalLink::makeAnchorLink(new ObjectId("Publisher", array(12)), new ObjectId("Book", array(13)), "book");
    $this->assertEquals("link://app.src.model.Publisher:12/app.src.model.Book:13#book", $link);
  }

  public function testIsLink() {
    $this->assertTrue(InternalLink::isLink("link://app.src.model.Publisher:12"));
    $this->assertTrue(InternalLink::isLink("link://app.src.model.Publisher:12/app.src.model.Book:13#book"));
  }

  public function testGetReferencedOID() {
    $oid1 = InternalLink::getReferencedOID("link://app.src.model.Publisher:12");
    $this->assertEquals("app.src.model.Publisher:12", $oid1->__toString());

    $oid2 = InternalLink::getReferencedOID("link://app.src.model.Publisher:12/app.src.model.Book:13#book");
    $this->assertEquals("app.src.model.Publisher:12", $oid2->__toString());
  }

  public function testGetAnchorOID() {
    $oid1 = InternalLink::getAnchorOID("link://app.src.model.Publisher:12");
    $this->assertNull($oid1);

    $oid2 = InternalLink::getAnchorOID("link://app.src.model.Publisher:12/app.src.model.Book:13#book");
    $this->assertEquals("app.src.model.Book:13", $oid2->__toString());
  }

  public function testGetAnchorName() {
    $name1 = InternalLink::getAnchorName("link://app.src.model.Publisher:12");
    $this->assertNull($name1);

    $name2 = InternalLink::getAnchorName("link://app.src.model.Publisher:12#publisher");
    $this->assertEquals("publisher", $name2);

    $name3 = InternalLink::getAnchorName("link://app.src.model.Publisher:12/app.src.model.Book:13#book");
    $this->assertEquals("book", $name3);
  }
}
?>