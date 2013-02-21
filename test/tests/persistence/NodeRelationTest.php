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

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;
use test\lib\TestUtil;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;

/**
 * NodeRelationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeRelationTest extends DatabaseTestCase {

  private $oids = array();

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array(id => 1),
      ),
      'Chapter' => array(
        array('id' => 300, 'fk_chapter_id' => 303, 'fk_author_id' => 304),
        array('id' => 302, 'fk_chapter_id' => 300, 'fk_author_id' => null),
        array('id' => 303, 'fk_chapter_id' => null, 'fk_author_id' => null),
      ),
      'Book' => array(
        array('id' => 301),
      ),
      'Author' => array(
        array('id' => 304),
      ),
      'Image' => array(
        array('id' => 305, 'fk_titlechapter_id' => 300, 'fk_chapter_id' => null),
        array('id' => 306, 'fk_titlechapter_id' => null, 'fk_chapter_id' => 300),
      ),
    ));
  }

  protected function setUp() {
    // setup the object tree
    $this->oids = array(
      'chapter' => new ObjectId('Chapter', 300),
      'book' => new ObjectId('Book', 301),
      'subChapter' => new ObjectId('Chapter', 302),
      'parentChapter' => new ObjectId('Chapter', 303),
      'author' => new ObjectId('Author', 304),
      'titleImage' => new ObjectId('Image', 305),
      'normalImage' => new ObjectId('Image', 306)
    );
    parent::setUp();
  }

  public function testRelations() {
    TestUtil::runAnonymous(true);
    //$this->enableProfiler('Chapter');

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $chapter = $persistenceFacade->load($this->oids['chapter'], 1);

    $curRelatives1 = $chapter->getChildrenEx(null, 'Book', null);
    $this->assertEquals(1, sizeof($curRelatives1));
    $this->assertEquals($this->oids['book'], $curRelatives1[0]->getOID());

    $curRelatives2 = $chapter->getChildrenEx(null, null, 'Book');
    $this->assertEquals(1, sizeof($curRelatives2));

    $curRelatives3 = $chapter->getChildrenEx(null, 'SubChapter', null);
    $this->assertEquals(1, sizeof($curRelatives3));
    $this->assertEquals($this->oids['subChapter'], $curRelatives3[0]->getOID());

    $curRelatives4 = $chapter->getParentsEx(null, 'ParentChapter', null);
    $this->assertEquals(1, sizeof($curRelatives4));
    $this->assertEquals($this->oids['parentChapter'], $curRelatives4[0]->getOID());

    $curRelatives5 = $chapter->getParentsEx(null, 'Author', null);
    $this->assertEquals(1, sizeof($curRelatives5));
    $this->assertEquals($this->oids['author'], $curRelatives5[0]->getOID());

    $curRelatives6 = $chapter->getChildrenEx(null, 'TitleImage', null);
    $this->assertEquals(1, sizeof($curRelatives6));
    $this->assertEquals($this->oids['titleImage'], $curRelatives6[0]->getOID());

    $curRelatives7 = $chapter->getChildrenEx(null, 'NormalImage', null);
    $this->assertEquals(1, sizeof($curRelatives7));
    $this->assertEquals($this->oids['normalImage'], $curRelatives7[0]->getOID());

    $curRelatives8 = $chapter->getChildrenEx(null, null, 'Image');
    $this->assertEquals(2, sizeof($curRelatives8));
    $transaction->rollback();

    //$this->printProfile('Chapter');
    TestUtil::runAnonymous(false);
  }

  public function testDeleteNode() {
    TestUtil::runAnonymous(true);
    //$this->enableProfiler('Chapter');

    // delete all relations
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $chapter1 = $persistenceFacade->load($this->oids['chapter'], 1);
    $chapter1->deleteNode($persistenceFacade->load($this->oids['book']));
    $chapter1->deleteNode($persistenceFacade->load($this->oids['subChapter']), 'SubChapter');
    $chapter1->deleteNode($persistenceFacade->load($this->oids['parentChapter']), 'ParentChapter');
    $chapter1->deleteNode($persistenceFacade->load($this->oids['author']));
    $chapter1->deleteNode($persistenceFacade->load($this->oids['titleImage']), 'TitleImage');
    $chapter1->deleteNode($persistenceFacade->load($this->oids['normalImage']), 'NormalImage');
    $transaction->commit();

    // test
    $transaction->begin();
    $chapter2 = $persistenceFacade->load($this->oids['chapter'], 1);
    $this->assertEquals(0, sizeof($chapter2->getChildrenEx(null, 'Book', null)));
    $this->assertEquals(0, sizeof($chapter2->getChildrenEx(null, 'SubChapter', null)));
    $this->assertEquals(0, sizeof($chapter2->getParentsEx(null, 'ParentChapter', null)));
    $this->assertEquals(0, sizeof($chapter2->getParentsEx(null, 'Author', null)));
    $this->assertEquals(0, sizeof($chapter2->getChildrenEx(null, 'TitleImage', null)));
    $this->assertEquals(0, sizeof($chapter2->getChildrenEx(null, 'NormalImage', null)));

    $book = $persistenceFacade->load($this->oids['book'], 1);
    $this->assertEquals(0, sizeof($book->getChildrenEx(null, 'Chapter', null)));

    $subChapter = $persistenceFacade->load($this->oids['subChapter'], 1);
    $this->assertEquals(0, sizeof($subChapter->getParentsEx(null, 'ParentChapter', null)));

    $parentChapter = $persistenceFacade->load($this->oids['parentChapter'], 1);
    $this->assertEquals(0, sizeof($parentChapter->getChildrenEx(null, 'SubChapter', null)));

    $author = $persistenceFacade->load($this->oids['author'], 1);
    $this->assertEquals(0, sizeof($author->getChildrenEx(null, 'Chapter', null)));

    $titleImage = $persistenceFacade->load($this->oids['titleImage'], 1);
    $this->assertEquals(0, sizeof($titleImage->getParentsEx(null, 'TitleChapter', null)));

    $normalImage = $persistenceFacade->load($this->oids['normalImage'], 1);
    $this->assertEquals(0, sizeof($normalImage->getParentsEx(null, 'NormalChapter', null)));
    $transaction->rollback();

    //$this->printProfile('Chapter');
    TestUtil::runAnonymous(false);
  }

  public function _testLoadSingle() {
    TestUtil::runAnonymous(true);
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $chapter = $persistenceFacade->load($this->oids['chapter'], BuildDepth::SINGLE);
    $chapter->loadChildren('Book', 1);
    $book = $chapter->getFirstChild('Book');
    echo "title: ".$book->getTitle()."\n";
    //*
    foreach($chapter->getValueNames() as $name) {
      $value = $chapter->getValue($name);
      echo $name.": ".$value."(".sizeof($value).")\n";
    }
    //*/
    TestUtil::runAnonymous(false);
  }

  public function testDelete() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $chapter = $persistenceFacade->load($this->oids['chapter'], 1);
    $chapter->delete();
    $transaction->commit();

    // test
    $transaction->begin();
    $this->assertEquals(null, $persistenceFacade->load($this->oids['chapter']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['book']));
    $this->assertEquals(null, $persistenceFacade->load($this->oids['subChapter']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['parentChapter']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['author']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['titleImage']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['normalImage']));
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }

  public function testNavigabilityManyToMany() {
    TestUtil::runAnonymous(true);

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $nmChapterBook = $persistenceFacade->loadFirstObject("NMChapterBook");
    // the Book is navigable from the NMChapterBook instance
    $book1 = $nmChapterBook->getValue("Book");
    $this->assertNotNull($book1);
    $transaction->rollback();

    $transaction->begin();
    $book2 = $persistenceFacade->load($book1->getOID());
    // the NMChapterBook is not navigable from the Book instance
    $nmChapterBooks = $book2->getValue("NMChapterBook");
    $this->assertNull($nmChapterBooks);
    // but the Chapter is
    $chapters = $book2->getValue("Chapter");
    $this->assertNotNull($chapters);
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>