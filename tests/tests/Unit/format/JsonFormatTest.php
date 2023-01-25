<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\format\Format;

use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\isTrue;

/**
 * JsonFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JsonFormatTest extends \Codeception\Test\Unit {

  public function testDeserializeSimple(): void {
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
    assertIsArray($data);

    assertThat($data['string'], equalTo('abc'));
    assertThat($data['integer'], equalTo(123));
    assertThat($data['boolean'], isTrue());
    assertIsArray($data['array']);

    $array1 = $data['array'];
    assertThat($array1['string'], equalTo('def'));
    assertThat($array1['integer'], equalTo(456));
    assertThat($array1['boolean'], isFalse());
    assertIsArray($array1['array']);

    $array2 = $array1['array'];
    assertThat($array2['string'], equalTo('ghi'));
    assertThat($array2['integer'], equalTo(789));
  }

  public function testDeserializeNodeSimple(): void {
    /** @var \wcmf\lib\security\PermissionManager $permissionManager */
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->withTempPermissions(function() {
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
      assertIsArray($data);

      $book = $data['app.src.model.Book:123'];
      assertInstanceOf(Book::class, $book);
      assertThat($book->getOID()->__toString(), equalTo('app.src.model.Book:123'));
      assertThat($book->getValue('title'), equalTo('Matrix - The Original'));

      assertThat($data['sid'], equalTo('cd65fec9bce4d7ec74e341a9031f8966'));
      assertArrayNotHasKey('attributes', $data);
      assertArrayNotHasKey('oid', $data);
      assertArrayNotHasKey('lastChange', $data);
    }, ['app.src.model.Book', '', PersistenceAction::READ]);
  }

  public function testDeserializeNodeHierarchy(): void {
    /** @var \wcmf\lib\security\PermissionManager $permissionManager */
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->withTempPermissions(function() {
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
                                        'name' => 'Chapter 3',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'oid' => 'app.src.model.Chapter:2',
                        'attributes' => [
                            'name' => 'Chapter 2',
                            'NormalImage' => [
                                [
                                    'oid' => 'app.src.model.Image:12',
                                    'attributes' => [
                                        'file' => 'image.png',
                                    ],
                                ],
                            ],
                            'Author' => [
                                'oid' => 'app.src.model.Author:1',
                                'attributes' => [
                                    'name' => 'Unknown',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $format = $this->createFormat();
        $format->deserialize($message);

        // test
        $data = $message->getValues();
        assertIsArray($data);

        $book = $data['app.src.model.Book:123'];
        assertInstanceOf(Book::class, $book);
        assertThat($book->getOID()->__toString(), equalTo('app.src.model.Book:123'));

        $chapters = $book->getValue('Chapter');
        assertCount(2, $chapters);

        $chapter1 = $chapters[0];
        assertThat($chapter1->getOID()->__toString(), equalTo('app.src.model.Chapter:1'));

        $subChapters = $chapter1->getValue('SubChapter');
        assertCount(1, $subChapters);
        assertThat($subChapters[0]->getOID()->__toString(), equalTo('app.src.model.Chapter:3'));

        $chapter2 = $chapters[1];
        assertThat($chapter2->getOID()->__toString(), equalTo('app.src.model.Chapter:2'));

        $author = $chapter2->getValue('Author');
        assertThat($author->getOID()->__toString(), equalTo('app.src.model.Author:1'));
    },
      ['app.src.model.Book', '', PersistenceAction::READ],
      ['app.src.model.Chapter', '', PersistenceAction::READ],
      ['app.src.model.Image', '', PersistenceAction::READ],
      ['app.src.model.Author', '', PersistenceAction::READ]
    );
  }

  public function testDeserializeNodeList(): void {
    /** @var \wcmf\lib\security\PermissionManager $permissionManager */
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $permissionManager->withTempPermissions(function() {
        $message = ObjectFactory::getNewInstance('request');
        $message->setValues(
            [
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => [
                    'type' => 'Chapter',
                    'content' => [
                        'contentType' => 'Chapter', [
                            'oid' => 'app.src.model.Chapter:1',
                            'attributes' => [
                                'name' => 'Chapter 1',
                            ],
                        ], [
                            'oid' => 'app.src.model.Chapter:2',
                            'attributes' => [
                                'name' => 'Chapter 2',
                            ],
                        ], [
                            'oid' => 'app.src.model.Chapter:3',
                            'attributes' => [
                                'name' => 'Chapter 3',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $format = $this->createFormat();
        $format->deserialize($message);

        // test
        $data = $message->getValues();
        assertIsArray($data);

        $list = $data['list'];
        assertIsArray($list);
        assertCount(2, array_keys($list));

        $chapters = $list['content'];
        assertCount(4, $chapters);
        assertThat($chapters['contentType'], equalTo('Chapter'));
        assertThat($chapters['app.src.model.Chapter:1']->getOID()->__toString(), equalTo('app.src.model.Chapter:1'));
        assertThat($chapters['app.src.model.Chapter:2']->getOID()->__toString(), equalTo('app.src.model.Chapter:2'));
        assertThat($chapters['app.src.model.Chapter:3']->getOID()->__toString(), equalTo('app.src.model.Chapter:3'));
    },
      ['app.src.model.Book', '', PersistenceAction::READ],
      ['app.src.model.Chapter', '', PersistenceAction::READ]
    );
  }

  public function testSerializeSimple(): void {
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
    ob_start();
    $format->serialize($message);
    ob_end_clean();

    // test
    $data = $message->getValues();
    assertIsArray($data);

    assertThat($data['string'], equalTo('abc'));
    assertThat($data['integer'], equalTo(123));
    assertThat($data['boolean'], isTrue());
    assertIsArray($data['array']);

    $array1 = $data['array'];
    assertThat($array1['string'], equalTo('def'));
    assertThat($array1['integer'], equalTo(456));
    assertThat($array1['boolean'], isFalse());
    assertIsArray($array1['array']);

    $array2 = $array1['array'];
    assertThat($array2['string'], equalTo('ghi'));
    assertThat($array2['integer'], equalTo(789));
  }

  public function testSerializeNodeSimple(): void {
    $book = new Book(new ObjectId('Book', [123]));
    $book->setValue('title', 'Matrix - The Original');
    $book->setValue('modified', '2011-10-01 00:01:01');

    $message = ObjectFactory::getNewInstance('response');
    $message->setValues([
                'app.src.model.Book:123' => $book,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
            ]);

    $format = $this->createFormat();
    ob_start();
    $format->serialize($message);
    ob_end_clean();

    // test
    $data = $message->getValues();
    assertIsArray($data);
    assertThat($data['sid'], equalTo('cd65fec9bce4d7ec74e341a9031f8966'));
    assertThat($data['oid'], equalTo('app.src.model.Book:123'));
    assertThat($data['className'], equalTo('app.src.model.Book'));
    assertThat($data['isReference'], isFalse());
    assertThat($data['lastChange'], equalTo(strtotime($book->getValue('modified'))));

    $attributes = $data['attributes'];
    assertIsArray($attributes);
    assertThat($attributes['title'], equalTo('Matrix - The Original'));
  }

  public function testSerializeNodeHierarchy(): void {
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
    ob_start();
    $format->serialize($message);
    ob_end_clean();

    // test
    $data = $message->getValues();
    assertIsArray($data);
    assertThat($data['sid'], equalTo('cd65fec9bce4d7ec74e341a9031f8966'));
    assertThat($data['oid'], equalTo('app.src.model.Book:123'));
    assertThat($data['className'], equalTo('app.src.model.Book'));
    assertThat($data['isReference'], isFalse());

    $bookAttributes = $data['attributes'];
    assertIsArray($bookAttributes);
    assertThat($bookAttributes['title'], equalTo('Matrix - The Original'));

    $chapters = $bookAttributes['Chapter'];
    assertIsArray($chapters);
    assertCount(2, $chapters);

    $chapter21 = $chapters[0];
    assertThat($chapter21['oid'], equalTo('app.src.model.Chapter:1'));
    $chapter1Attributes = $chapter21['attributes'];
    assertThat($chapter1Attributes['name'], equalTo('Chapter 1'));

    $subChapters = $chapter1Attributes['SubChapter'];
    assertCount(1, $subChapters);
    $subChapter = $subChapters[0];
    assertThat($subChapter['oid'], equalTo('app.src.model.Chapter:3'));
    $subChapterAttributes = $subChapter['attributes'];
    assertThat($subChapterAttributes['name'], equalTo('Chapter 3'));

    $chapter22 = $chapters[1];
    assertThat($chapter22['oid'], equalTo('app.src.model.Chapter:2'));
    $chapter2Attributes = $chapter22['attributes'];
    assertThat($chapter2Attributes['name'], equalTo('Chapter 2'));

    $author2 = $chapter2Attributes['Author'];
    assertThat($author2['oid'], equalTo('app.src.model.Author:1'));
    $authorAttributes = $author2['attributes'];
    assertThat($authorAttributes['name'], equalTo('Unknown'));
  }

  public function testSerializeNodeList(): void {
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
    ob_start();
    $format->serialize($message);
    ob_end_clean();

    // test
    $data = $message->getValues();
    assertIsArray($data);

    $list2 = $data['list'];
    assertIsArray($list2);
    assertCount(2, array_keys($list2));

    $chapters = $list2['content'];
    assertCount(4, $chapters);
    assertThat($chapters['contentType'], equalTo('Chapter'));

    assertThat($chapters[0]['className'], equalTo('app.src.model.Chapter'));
    assertThat($chapters[0]['oid'], equalTo('app.src.model.Chapter:1'));
    assertThat($chapters[1]['className'], equalTo('app.src.model.Chapter'));
    assertThat($chapters[1]['oid'], equalTo('app.src.model.Chapter:2'));
    assertThat($chapters[2]['className'], equalTo('app.src.model.Chapter'));
    assertThat($chapters[2]['oid'], equalTo('app.src.model.Chapter:3'));
  }

  private function createFormat(): Format {
    $serializer = ObjectFactory::getInstanceOf('wcmf\lib\model\impl\DionysosNodeSerializer');
    return ObjectFactory::getInstanceOf('wcmf\lib\presentation\format\impl\JsonFormat',
            ['serializer' => $serializer]);
  }
}
?>