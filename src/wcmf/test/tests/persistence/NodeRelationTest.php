<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\persistence;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\util\TestUtil;

/**
 * NodeRelationTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeRelationTest extends DatabaseTestCase {

  private $oids = [];

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        ['table' => ''],
      ),
      'User' => array(
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ),
      'NMUserRole' => array(
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ),
      'Role' => array(
        ['id' => 0, 'name' => 'administrators'],
      ),
      'Publisher' => array(
        array('id' => 200),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 203),
        array('id' => 202, 'fk_publisher_id' => 200, 'fk_author_id' => 204),
      ),
      'Author' => array(
        array('id' => 203),
        array('id' => 204),
      ),
      'Book' => array(
        array('id' => 205),
      ),
      'Chapter' => array(
        array('id' => 300, 'fk_chapter_id' => 303, 'fk_author_id' => 203, 'fk_book_id' => 205),
        array('id' => 302, 'fk_chapter_id' => 300, 'fk_author_id' => 203, 'fk_book_id' => null),
        array('id' => 303, 'fk_chapter_id' => null, 'fk_author_id' => 203, 'fk_book_id' => null),
      ),
      'Image' => array(
        array('id' => 305, 'fk_titlechapter_id' => 300, 'fk_chapter_id' => null),
        array('id' => 306, 'fk_titlechapter_id' => null, 'fk_chapter_id' => 300),
      ),
    ));
  }

  protected function setUp() {
    parent::setUp();
    // setup the object tree
    $this->oids = array(
      'publisher' => new ObjectId('Publisher', 200),
      'author1' => new ObjectId('Author', 203),
      'author2' => new ObjectId('Author', 204),
      'book' => new ObjectId('Book', 205),
      'chapter' => new ObjectId('Chapter', 300),
      'subChapter1' => new ObjectId('Chapter', 302),
      'subChapter2' => new ObjectId('Chapter', 300),
      'parentChapter' => new ObjectId('Chapter', 303),
      'titleImage' => new ObjectId('Image', 305),
      'normalImage' => new ObjectId('Image', 306)
    );
  }

  public function testRelations() {
    TestUtil::startSession('admin', 'admin');

    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $publisher = $persistenceFacade->load($this->oids['publisher'], 3);

    $authors1 = $publisher->getChildrenEx(null, 'Author', null);
    $this->assertEquals(2, sizeof($authors1));
    $this->assertEquals($this->oids['author1'], $authors1[0]->getOID());

    $author = $publisher->getFirstChild(null, 'Author', null);
    $chapters = $author->getChildrenEx(null, 'Chapter', null);
    $this->assertEquals(3, sizeof($chapters));

    $chapter1 = $author->getFirstChild(null, 'Chapter', null);
    $subChapters1 = $chapter1->getChildrenEx(null, 'SubChapter', null);
    $this->assertEquals(1, sizeof($subChapters1));
    $this->assertEquals($this->oids['subChapter1'], $subChapters1[0]->getOID());

    $chapter2 = $chapters[1];
    $subChapters2 = $chapter2->getChildrenEx(null, 'SubChapter', null);
    $this->assertEquals(0, sizeof($subChapters2));

    $chapter3 = $chapters[2];
    $subChapters3 = $chapter3->getChildrenEx(null, 'SubChapter', null);
    $this->assertEquals(1, sizeof($subChapters3));
    $this->assertEquals($this->oids['subChapter2'], $subChapters3[0]->getOID());

    $parentChapters = $chapter1->getParentsEx(null, 'ParentChapter', null);
    $this->assertEquals(1, sizeof($parentChapters));
    $this->assertEquals($this->oids['parentChapter'], $parentChapters[0]->getOID());

    $authors2 = $chapter1->getParentsEx(null, 'Author', null);
    $this->assertEquals(1, sizeof($authors2));
    $this->assertEquals($this->oids['author1'], $authors2[0]->getOID());

    $titleImages = $chapter1->getChildrenEx(null, 'TitleImage', null);
    $this->assertEquals(1, sizeof($titleImages));
    $this->assertEquals($this->oids['titleImage'], $titleImages[0]->getOID());

    $normalImages = $chapter1->getChildrenEx(null, 'NormalImage', null);
    $this->assertEquals(1, sizeof($normalImages));
    $this->assertEquals($this->oids['normalImage'], $normalImages[0]->getOID());

    $images = $chapter1->getChildrenEx(null, null, 'Image');
    $this->assertEquals(2, sizeof($images));
    $transaction->rollback();

    //$this->printProfile('Chapter');
    TestUtil::endSession();
  }

  public function testDeleteNode() {
    TestUtil::startSession('admin', 'admin');

    // delete all relations
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    $chapter1 = $persistenceFacade->load($this->oids['chapter'], 1);
    $chapter1->deleteNode($persistenceFacade->load($this->oids['book']));
    $chapter1->deleteNode($persistenceFacade->load($this->oids['subChapter1']), 'SubChapter');
    $chapter1->deleteNode($persistenceFacade->load($this->oids['parentChapter']), 'ParentChapter');
    $chapter1->deleteNode($persistenceFacade->load($this->oids['author1']));
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

    $subChapter = $persistenceFacade->load($this->oids['subChapter1'], 1);
    $this->assertEquals(0, sizeof($subChapter->getParentsEx(null, 'ParentChapter', null)));

    $parentChapter = $persistenceFacade->load($this->oids['parentChapter'], 1);
    $this->assertEquals(0, sizeof($parentChapter->getChildrenEx(null, 'SubChapter', null)));

    $author = $persistenceFacade->load($this->oids['author1'], 1);
    $this->assertEquals(2, sizeof($author->getChildrenEx(null, 'Chapter', null)));

    $titleImage = $persistenceFacade->load($this->oids['titleImage'], 1);
    $this->assertEquals(0, sizeof($titleImage->getParentsEx(null, 'TitleChapter', null)));

    $normalImage = $persistenceFacade->load($this->oids['normalImage'], 1);
    $this->assertEquals(0, sizeof($normalImage->getParentsEx(null, 'NormalChapter', null)));
    $transaction->rollback();

    //$this->printProfile('Chapter');
    TestUtil::endSession();
  }

  public function testLoadNM() {
    TestUtil::startSession('admin', 'admin');
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');

    $author1 = $persistenceFacade->load($this->oids['author1'], BuildDepth::SINGLE);
    $publisher1 = $author1->getValue('Publisher');
    $this->assertEquals(1, sizeof($publisher1));

    $author2 = $persistenceFacade->load($this->oids['author2'], BuildDepth::SINGLE);
    $publisher2 = $author2->getValue('Publisher');
    $this->assertEquals(1, sizeof($publisher2));
    TestUtil::endSession();
  }

  public function testDelete() {
    TestUtil::startSession('admin', 'admin');

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
    $this->assertEquals(null, $persistenceFacade->load($this->oids['subChapter1']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['parentChapter']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['author1']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['titleImage']));
    $this->assertNotEquals(null, $persistenceFacade->load($this->oids['normalImage']));
    $transaction->rollback();

    TestUtil::endSession();
  }

  public function testNavigabilityManyToMany() {
    TestUtil::startSession('admin', 'admin');

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

    TestUtil::endSession();
  }
}
?>