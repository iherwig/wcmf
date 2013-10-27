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
        array('id' => 1),
      ),
      'Publisher' => array(
        array('id' => 200),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 202),
      ),
      'Author' => array(
        array('id' => 202),
      ),
      'Book' => array(
        array('id' => 203),
      ),
      'Chapter' => array(
        array('id' => 300, 'fk_chapter_id' => 303, 'fk_author_id' => 202, 'fk_book_id' => 203),
        array('id' => 302, 'fk_chapter_id' => 300, 'fk_author_id' => 202, 'fk_book_id' => null),
        array('id' => 303, 'fk_chapter_id' => null, 'fk_author_id' => 202, 'fk_book_id' => null),
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
      'publisher' => new ObjectId('Publisher', 200),
      'author' => new ObjectId('Author', 202),
      'book' => new ObjectId('Book', 203),
      'chapter' => new ObjectId('Chapter', 300),
      'subChapter' => new ObjectId('Chapter', 302),
      'parentChapter' => new ObjectId('Chapter', 303),
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
    $publisher = $persistenceFacade->load($this->oids['publisher'], 3);

    $authors1 = $publisher->getChildrenEx(null, 'Author', null);
    $this->assertEquals(1, sizeof($authors1));
    $this->assertEquals($this->oids['author'], $authors1[0]->getOID());

    $author = $publisher->getFirstChild(null, 'Author', null);
    $chapters = $author->getChildrenEx(null, 'Chapter', null);
    $this->assertEquals(3, sizeof($chapters));

    $chapter = $author->getFirstChild(null, 'Chapter', null);
    $subChapters = $chapter->getChildrenEx(null, 'SubChapter', null);
    $this->assertEquals(1, sizeof($subChapters));
    $this->assertEquals($this->oids['subChapter'], $subChapters[0]->getOID());

    $parentChapters = $chapter->getParentsEx(null, 'ParentChapter', null);
    $this->assertEquals(1, sizeof($parentChapters));
    $this->assertEquals($this->oids['parentChapter'], $parentChapters[0]->getOID());

    $authors2 = $chapter->getParentsEx(null, 'Author', null);
    $this->assertEquals(1, sizeof($authors2));
    $this->assertEquals($this->oids['author'], $authors2[0]->getOID());

    $titleImages = $chapter->getChildrenEx(null, 'TitleImage', null);
    $this->assertEquals(1, sizeof($titleImages));
    $this->assertEquals($this->oids['titleImage'], $titleImages[0]->getOID());

    $normalImages = $chapter->getChildrenEx(null, 'NormalImage', null);
    $this->assertEquals(1, sizeof($normalImages));
    $this->assertEquals($this->oids['normalImage'], $normalImages[0]->getOID());

    $images = $chapter->getChildrenEx(null, null, 'Image');
    $this->assertEquals(2, sizeof($images));
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
    $this->assertEquals(0, sizeof($chapter2->getParentsEx(null, 'Book', null)));
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
    $this->assertEquals(2, sizeof($author->getChildrenEx(null, 'Chapter', null)));

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
    $nmPublisherAuthor1 = $persistenceFacade->loadFirstObject("NMPublisherAuthor");
    // the Publisher is navigable from the NMPublisherAuthor instance
    $publisher1 = $nmPublisherAuthor1->getValue("Publisher");
    $this->assertNotNull($publisher1);
    $transaction->rollback();

    $transaction->begin();
    $publisher2 = $persistenceFacade->load($publisher1->getOID());
    // the NMPublisherAuthor is not navigable from the Publisher instance
    $nmPublisherAuthor2 = $publisher2->getValue("NMPublisherAuthor");
    $this->assertNull($nmPublisherAuthor2);
    // but the Author is
    $authors = $publisher2->getValue("Author");
    $this->assertNotNull($authors);
    $transaction->rollback();

    TestUtil::runAnonymous(false);
  }
}
?>