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
namespace test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\mapper\RDBManyToManyRelationDescription;
use wcmf\lib\model\mapper\RDBManyToOneRelationDescription;
use wcmf\lib\model\mapper\RDBOneToManyRelationDescription;

/**
 * RelationDescriptionTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RelationDescriptionTest extends \PHPUnit_Framework_TestCase {

  public function testMapper() {
    $rel1 = new RDBOneToManyRelationDescription('Page', 'ParentPage', 'Page', 'ChildPage',
            '1', '1', '0', 'unbounded', 'composite', 'none', 'true', 'true', 'child', 'id', 'fk_page_id');
    $this->assertEquals(ObjectFactory::getInstance('persistenceFacade')->getMapper('Page'), $rel1->getThisMapper());
    $this->assertEquals(ObjectFactory::getInstance('persistenceFacade')->getMapper('Page'), $rel1->getOtherMapper());
  }

  public function testCompare() {
    // to same type
    $rel1 = new RDBOneToManyRelationDescription('Page', 'ParentPage', 'Page', 'ChildPage',
            '1', '1', '0', 'unbounded', 'composite', 'none', 'true', 'true', 'child', 'id', 'fk_page_id');
    $rel2 = new RDBManyToOneRelationDescription('Page', 'ChildPage', 'Page', 'ParentPage',
            '0', 'unbounded', '1', '1', 'none', 'composite', 'true', 'true', 'parent', 'id', 'fk_page_id');
    $this->assertTrue($rel1->isSameRelation($rel2));

    // to other type
    $rel1 = new RDBOneToManyRelationDescription('Page', 'TitlePage', 'Image', 'TitleImage',
            '1', '1', '0', '1', 'composite', 'none', 'true', 'true', 'child', 'id', 'fk_titlepage_id');
    $rel2 = new RDBManyToOneRelationDescription('Image', 'TitleImage', 'Page', 'TitlePage',
            '0', '1', '1', '1', 'none', 'composite', 'true', 'true', 'parent', 'id', 'fk_titlepage_id');
    $this->assertTrue($rel1->isSameRelation($rel2));

    // many to mayn
    $rel1 = new RDBManyToManyRelationDescription(
        /* this -> nm  */ new RDBOneToManyRelationDescription('Page', 'Page', 'NMPageDocument', 'NMPageDocument',
                '1', '1', '0', 'unbounded', 'shared', 'none', 'true', 'true', 'child', 'id', 'fk_page_id'),
        /* nm -> other */ new RDBManyToOneRelationDescription('NMPageDocument', 'NMPageDocument', 'Document', 'Document',
                '0', 'unbounded', '1', '1', 'none', 'shared', 'true', 'true', 'parent', 'id', 'fk_document_id')
        );
    $rel2 = new RDBManyToManyRelationDescription(
        /* this -> nm  */ new RDBOneToManyRelationDescription('Document', 'Document', 'NMPageDocument', 'NMPageDocument',
                '1', '1', '0', 'unbounded', 'shared', 'none', 'true', 'true', 'child', 'id', 'fk_document_id'),
        /* nm -> other */ new RDBManyToOneRelationDescription('NMPageDocument', 'NMPageDocument', 'Page', 'Page',
                '0', 'unbounded', '1', '1', 'none', 'shared', 'true', 'true', 'parent', 'id', 'fk_page_id')
        );
    $this->assertTrue($rel1->isSameRelation($rel2));
  }
}
?>