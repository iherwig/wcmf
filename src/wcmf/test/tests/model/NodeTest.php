<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\test\tests\model;

use wcmf\test\lib\BaseTestCase;

use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;

/**
 * NodeTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class NodeTest extends BaseTestCase {

  public function testBuildDepth() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();

    $publisher1 = $persistenceFacade->create('Publisher', BuildDepth::SINGLE);
    $this->assertEquals(0, sizeof($publisher1->getValue('Author')));
    $this->assertEquals(0, sizeof($publisher1->getValue('Book')));

    $publisher2 = $persistenceFacade->create('Publisher', BuildDepth::REQUIRED);
    $this->assertEquals(0, sizeof($publisher2->getValue('Author')));
    $this->assertEquals(1, sizeof($publisher2->getValue('Book')));

    $publisher3 = $persistenceFacade->create('Publisher', 1);
    $this->assertEquals(1, sizeof($publisher3->getValue('Author')));
    $this->assertEquals(1, sizeof($publisher3->getValue('Book')));

    $book1 = $persistenceFacade->create('Book', BuildDepth::SINGLE);
    $this->assertEquals(0, sizeof($book1->getValue('Chapter')));

    $book2 = $persistenceFacade->create('Book', 1);
    $this->assertEquals(1, sizeof($book2->getValue('Chapter')));
    $book2Chapters = $book2->getValue('Chapter');
    $book2Chapter = $book2Chapters[0];
    $this->assertEquals(0, sizeof($book2Chapter->getValue('SubChapter')));
    $this->assertEquals(0, sizeof($book2Chapter->getValue('NormalImage')));
    $this->assertEquals(0, sizeof($book2Chapter->getValue('TitleImage')));
    $this->assertEquals(0, sizeof($book2Chapter->getValue('Author')));

    $book3 = $persistenceFacade->create('Book', 2);
    $this->assertEquals(1, sizeof($book3->getValue('Chapter')));
    $book3Chapters = $book3->getValue('Chapter');
    $book3Chapter = $book3Chapters[0];
    $this->assertEquals(1, sizeof($book3Chapter->getValue('SubChapter')));
    $this->assertEquals(1, sizeof($book3Chapter->getValue('NormalImage')));
    $this->assertEquals(1, sizeof($book3Chapter->getValue('TitleImage')));
    $this->assertEquals(0, sizeof($book3Chapter->getValue('Author')));

    $chapter1 = $persistenceFacade->create('Chapter', 2);
    $subChapter1 = $chapter1->getFirstChild('SubChapter');
    $this->assertEquals(1, sizeof($subChapter1->getValue('SubChapter')));
    $this->assertEquals(1, sizeof($subChapter1->getValue('ParentChapter')));
    $subChapter2 = $subChapter1->getFirstChild('SubChapter');
    $this->assertEquals(0, sizeof($subChapter2->getValue('SubChapter')));
    $this->assertEquals(1, sizeof($subChapter2->getValue('ParentChapter')));

    $normalImage = $chapter1->getFirstChild('NormalImage');
    $this->assertEquals(1, sizeof($normalImage->getValue('NormalChapter')));

    $titleImage = $chapter1->getFirstChild('TitleImage');
    $this->assertEquals(1, sizeof($titleImage->getValue('TitleChapter')));

    $chapter2 = $book3->getFirstChild('Chapter');
    $this->assertEquals(1, sizeof($chapter2->getValue('Book')));
    $book4 = $chapter2->getValue('Book');
    $this->assertEquals($book4, $book3);

    $author1 = $persistenceFacade->create('Author', BuildDepth::SINGLE);
    $this->assertEquals(0, sizeof($author1->getValue('Chapter')));

    $author2 = $persistenceFacade->create('Author', 1);
    $this->assertEquals(1, sizeof($author2->getValue('Chapter')));

    // BuildDepth::INFINTE is not allowed for create
    try {
      $persistenceFacade->create('Chapter', BuildDepth::INFINITE);
      $this->fail('An expected exception has not been raised.');
    }
    catch(IllegalArgumentException $ex) {
    }

    $transaction->rollback();
  }
}
?>