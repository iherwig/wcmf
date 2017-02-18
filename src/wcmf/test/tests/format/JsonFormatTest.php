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
namespace wcmf\test\tests\format;

use app\src\model\Author;
use app\src\model\Book;
use app\src\model\Chapter;
use app\src\model\Image;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\ObjectId;
use wcmf\test\lib\BaseTestCase;

/**
 * JsonFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JsonFormatTest extends BaseTestCase {

  public function testDeserializeSimple() {
    $message = ObjectFactory::getNewInstance('request');
    $message->setValues([
                'string' => 'abc',
                'integer' => 123,
                'boolean' => true,
                'array' => [
                    'string' => 'def',
                    'integer' => 456,
                    'boolean' => false,
                    'array' => [
                        'string' => 'ghi',
                        'integer' => 789
                    ]
                ]
            ]);

    $format = $this->createFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $this->assertEquals('abc', $data['string']);
    $this->assertEquals(123, $data['integer']);
    $this->assertEquals(true, $data['boolean']);
    $this->assertTrue(is_array($data['array']));

    $array1 = $data['array'];
    $this->assertEquals('def', $array1['string']);
    $this->assertEquals(456, $array1['integer']);
    $this->assertEquals(false, $array1['boolean']);
    $this->assertTrue(is_array($array1['array']));

    $array2 = $array1['array'];
    $this->assertEquals('ghi', $array2['string']);
    $this->assertEquals(789, $array2['integer']);
  }

  public function testDeserializeNodeSimple() {
    $message = ObjectFactory::getNewInstance('request');
    $message->setValues([
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'oid' => 'app.src.model.Book:123',
                'lastChange' => 1234567890,
                'attributes' => [
                    'title' => 'Matrix - The Original'
                ]
            ]);

    $format = $this->createFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $book = $data['app.src.model.Book:123'];
    $this->assertTrue($book instanceof Book);
    $this->assertEquals('app.src.model.Book:123', $book->getOID()->__toString());
    $this->assertEquals('Matrix - The Original', $book->getValue('title'));

    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertFalse(isset($data['attributes']));
    $this->assertFalse(isset($data['oid']));
    $this->assertFalse(isset($data['lastChange']));
  }

  public function testDeserializeNodeHierarchy() {
    $message = ObjectFactory::getNewInstance('request');
    $message->setValues([
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'oid' => 'app.src.model.Book:123',
                'lastChange' => 1234567890,
                'attributes' => [
                    'title' => 'Matrix - The Original',
                    'Chapter' => [
                        [
                            'oid' => 'app.src.model.Chapter:1',
                            'attributes' => [
                                'name' => 'Chapter 1',
                                'SubChapter' => [
                                    [
                                        'oid' => 'app.src.model.Chapter:3',
                                        'attributes' => [
                                            'name' => 'Chapter 3'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'oid' => 'app.src.model.Chapter:2',
                            'attributes' => [
                                'name' => 'Chapter 2',
                                'NormalImage' => [
                                    [
                                        'oid' => 'app.src.model.Image:12',
                                        'attributes' => [
                                            'file' => 'image.png'
                                        ]
                                    ]
                                ],
                                'Author' => [
                                    'oid' => 'app.src.model.Author:1',
                                    'attributes' => [
                                        'name' => 'Unknown'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

    $format = $this->createFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $book = $data['app.src.model.Book:123'];
    $this->assertTrue($book instanceof Book);
    $this->assertEquals('app.src.model.Book:123', $book->getOID()->__toString());

    $chapters = $book->getValue('Chapter');
    $this->assertEquals(2, sizeof($chapters));

    $chapter1 = $chapters[0];
    $this->assertEquals('app.src.model.Chapter:1', $chapter1->getOID()->__toString());

    $subChapters = $chapter1->getValue('SubChapter');
    $this->assertEquals(1, sizeof($subChapters));
    $this->assertEquals('app.src.model.Chapter:3', $subChapters[0]->getOID()->__toString());

    $chapter2 = $chapters[1];
    $this->assertEquals('app.src.model.Chapter:2', $chapter2->getOID()->__toString());

    $author = $chapter2->getValue('Author');
    $this->assertEquals('app.src.model.Author:1', $author->getOID()->__toString());
  }

  public function testDeserializeNodeList() {
    $message = ObjectFactory::getNewInstance('request');
    $message->setValues([
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => [
                    'type' => 'Chapter',
                    'content' => [
                        'contentType' => 'Chapter', [
                            'oid' => 'app.src.model.Chapter:1',
                            'attributes' => [
                                'name' => 'Chapter 1'
                            ]
                        ], [
                            'oid' => 'app.src.model.Chapter:2',
                            'attributes' => [
                                'name' => 'Chapter 2'
                            ]
                        ], [
                            'oid' => 'app.src.model.Chapter:3',
                            'attributes' => [
                                'name' => 'Chapter 3'
                            ]
                        ]
                    ]
                ]
            ]
        );

    $format = $this->createFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $list = $data['list'];
    $this->assertTrue(is_array($list));
    $this->assertEquals(2, sizeof(array_keys($list)));

    $chapters = $list['content'];
    $this->assertEquals(4, sizeof($chapters));
    $this->assertEquals('Chapter', $chapters['contentType']);
    $this->assertEquals('app.src.model.Chapter:1', $chapters['app.src.model.Chapter:1']->getOID()->__toString());
    $this->assertEquals('app.src.model.Chapter:2', $chapters['app.src.model.Chapter:2']->getOID()->__toString());
    $this->assertEquals('app.src.model.Chapter:3', $chapters['app.src.model.Chapter:3']->getOID()->__toString());
  }

  public function testSerializeSimple() {
    $message = ObjectFactory::getNewInstance('response');
    $message->setValues([
                'string' => 'abc',
                'integer' => 123,
                'boolean' => true,
                'array' => [
                    'string' => 'def',
                    'integer' => 456,
                    'boolean' => false,
                    'array' => [
                        'string' => 'ghi',
                        'integer' => 789
                    ]
                ]
            ]);

    $format = $this->createFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $this->assertEquals('abc', $data['string']);
    $this->assertEquals(123, $data['integer']);
    $this->assertEquals(true, $data['boolean']);
    $this->assertTrue(is_array($data['array']));

    $array1 = $data['array'];
    $this->assertEquals('def', $array1['string']);
    $this->assertEquals(456, $array1['integer']);
    $this->assertEquals(false, $array1['boolean'] );
    $this->assertTrue(is_array($array1['array']));

    $array2 = $array1['array'];
    $this->assertEquals('ghi', $array2['string']);
    $this->assertEquals(789, $array2['integer']);
  }

  public function testSerializeNodeSimple() {
    $book = new Book(new ObjectId('Book', [123]));
    $book->setValue('title', 'Matrix - The Original');
    $book->setValue('modified', '2011-10-01 00:01:01');

    $message = ObjectFactory::getNewInstance('response');
    $message->setValues([
                'app.src.model.Book:123' => $book,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
            ]);

    $format = $this->createFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('app.src.model.Book:123', $data['oid']);
    $this->assertEquals('app.src.model.Book', $data['className']);
    $this->assertEquals(false, $data['isReference']);
    $this->assertEquals(1317420061, $data['lastChange']);

    $attributes = $data['attributes'];
    $this->assertTrue(is_array($attributes));
    $this->assertEquals('Matrix - The Original', $attributes['title']);
  }

  public function testSerializeNodeHierarchy() {
    $book1 = new Book(new ObjectId('Book', [123]));
    $book1->setValue('title', 'Matrix - The Original');
    $book1->setValue('modified', 1234567890);

    $chapter11 = new Chapter(new ObjectId('Chapter', [1]));
    $chapter11->setValue('name', 'Chapter 1');
    $chapter12 = new Chapter(new ObjectId('Chapter', [2]));
    $chapter12->setValue('name', 'Chapter 2');
    $chapter13 = new Chapter(new ObjectId('Chapter', [3]));
    $chapter13->setValue('name', 'Chapter 3');

    $image1 = new Image(new ObjectId('Image', [12]));
    $image1->setValue('file', 'image.png');

    $author1 = new Author(new ObjectId('Author', [1]));
    $author1->setValue('name', 'Unknown');

    $chapter11->addNode($chapter13, 'SubChapter');
    $chapter12->addNode($image1, 'NormalImage');
    $chapter12->addNode($author1, 'Author');

    $book1->addNode($chapter11, 'Chapter');
    $book1->addNode($chapter12, 'Chapter');

    $message = ObjectFactory::getNewInstance('response');
    $message->setValues([
                'app.src.model.Book:123' => $book1,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
        ]);

    $format = $this->createFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('app.src.model.Book:123', $data['oid']);
    $this->assertEquals('app.src.model.Book', $data['className']);
    $this->assertEquals(false, $data['isReference']);

    $bookAttributes = $data['attributes'];
    $this->assertTrue(is_array($bookAttributes));
    $this->assertEquals('Matrix - The Original', $bookAttributes['title']);

    $chapters = $bookAttributes['Chapter'];
    $this->assertTrue(is_array($chapters));
    $this->assertEquals(2, sizeof($chapters));

    $chapter21 = $chapters[0];
    $this->assertEquals('app.src.model.Chapter:1', $chapter21['oid']);
    $chapter1Attributes = $chapter21['attributes'];
    $this->assertEquals('Chapter 1', $chapter1Attributes['name']);

    $subChapters = $chapter1Attributes['SubChapter'];
    $this->assertEquals(1, sizeof($subChapters));
    $subChapter = $subChapters[0];
    $this->assertEquals('app.src.model.Chapter:3', $subChapter['oid']);
    $subChapterAttributes = $subChapter['attributes'];
    $this->assertEquals('Chapter 3', $subChapterAttributes['name']);

    $chapter22 = $chapters[1];
    $this->assertEquals('app.src.model.Chapter:2', $chapter22['oid']);
    $chapter2Attributes = $chapter22['attributes'];
    $this->assertEquals('Chapter 2', $chapter2Attributes['name']);

    $author2 = $chapter2Attributes['Author'];
    $this->assertEquals('app.src.model.Author:1', $author2['oid']);
    $authorAttributes = $author2['attributes'];
    $this->assertEquals('Unknown', $authorAttributes['name']);
  }

  public function testSerializeNodeList() {
    $chapter1 = new Chapter(new ObjectId('Chapter', [1]));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter2 = new Chapter(new ObjectId('Chapter', [2]));
    $chapter2->setValue('name', 'Chapter 2');
    $chapter3 = new Chapter(new ObjectId('Chapter', [3]));
    $chapter3->setValue('name', 'Chapter 3');

    $list1 = ['type' => 'Chapter',
        'content' => ['contentType' => 'Chapter', $chapter1, $chapter2, $chapter3]];

    $message = ObjectFactory::getNewInstance('response');
    $message->setValues([
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => $list1]);

    $format = $this->createFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $list2 = $data['list'];
    $this->assertTrue(is_array($list2));
    $this->assertEquals(2, sizeof(array_keys($list2)));

    $chapters = $list2['content'];
    $this->assertEquals(4, sizeof($chapters));
    $this->assertEquals('Chapter', $chapters['contentType']);

    $this->assertEquals('app.src.model.Chapter', $chapters[0]['className']);
    $this->assertEquals('app.src.model.Chapter:1', $chapters[0]['oid']);
    $this->assertEquals('app.src.model.Chapter', $chapters[1]['className']);
    $this->assertEquals('app.src.model.Chapter:2', $chapters[1]['oid']);
    $this->assertEquals('app.src.model.Chapter', $chapters[2]['className']);
    $this->assertEquals('app.src.model.Chapter:3', $chapters[2]['oid']);
  }

  private function createFormat() {
    $serializer = ObjectFactory::getInstanceOf('wcmf\lib\model\impl\DionysosNodeSerializer');
    return ObjectFactory::getInstanceOf('wcmf\lib\presentation\format\impl\JsonFormat',
            ['serializer' => $serializer]);
  }
}
?>