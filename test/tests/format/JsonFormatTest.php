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
namespace test\tests\format;

use test\lib\BaseTestCase;

use testapp\application\model\Author;
use testapp\application\model\Book;
use testapp\application\model\Chapter;
use testapp\application\model\Image;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\impl\JsonFormat;

/**
 * JsonFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JsonFormatTest extends BaseTestCase {

  public function testDeserializeSimple() {
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'string' => 'abc',
                'integer' => 123,
                'boolean' => true,
                'array' => array(
                    'string' => 'def',
                    'integer' => 456,
                    'boolean' => false,
                    'array' => array(
                        'string' => 'ghi',
                        'integer' => 789
                    )
                )
            ));

    $format = new JsonFormat();
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
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'oid' => 'Book:123',
                'lastChange' => 1234567890,
                'attributes' => array(
                    'title' => 'Matrix - The Original'
                )
            ));

    $format = new JsonFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $book = $data['Book:123'];
    $this->assertTrue($book instanceof Book);
    $this->assertEquals('Book:123', $book->getOID()->__toString());
    $this->assertEquals('Matrix - The Original', $book->getValue('title'));

    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertFalse(isset($data['attributes']));
    $this->assertFalse(isset($data['oid']));
    $this->assertFalse(isset($data['lastChange']));
  }

  public function testDeserializeNodeHierarchy() {
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'oid' => 'Book:123',
                'lastChange' => 1234567890,
                'attributes' => array(
                    'title' => 'Matrix - The Original',
                    'Chapter' => array(
                        array(
                            'oid' => 'Chapter:1',
                            'attributes' => array(
                                'name' => 'Chapter 1',
                                'SubChapter' => array(
                                    array(
                                        'oid' => 'Chapter:3',
                                        'attributes' => array(
                                            'name' => 'Chapter 3'
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'oid' => 'Chapter:2',
                            'attributes' => array(
                                'name' => 'Chapter 2',
                                'NormalImage' => array(
                                    array(
                                        'oid' => 'Image:12',
                                        'attributes' => array(
                                            'file' => 'image.png'
                                        )
                                    )
                                ),
                                'Author' => array(
                                    'oid' => 'Author:1',
                                    'attributes' => array(
                                        'name' => 'Unknown'
                                    )
                                )
                            )
                        )
                    )
                )
            ));

    $format = new JsonFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $book = $data['Book:123'];
    $this->assertTrue($book instanceof Book);
    $this->assertEquals('Book:123', $book->getOID()->__toString());

    $chapters = $book->getValue('Chapter');
    $this->assertEquals(2, sizeof($chapters));

    $chapter1 = $chapters[0];
    $this->assertEquals('Chapter:1', $chapter1->getOID()->__toString());

    $subChapters = $chapter1->getValue('SubChapter');
    $this->assertEquals(1, sizeof($subChapters));
    $this->assertEquals('Chapter:3', $subChapters[0]->getOID()->__toString());

    $chapter2 = $chapters[1];
    $this->assertEquals('Chapter:2', $chapter2->getOID()->__toString());

    $author = $chapter2->getValue('Author');
    $this->assertEquals('Author:1', $author->getOID()->__toString());
  }

  public function testDeserializeNodeList() {
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => array(
                    'type' => 'Chapter',
                    'content' => array(
                        'contentType' => 'Chapter',
                        array(
                            'oid' => 'Chapter:1',
                            'attributes' => array(
                                'name' => 'Chapter 1'
                            )
                        ),
                        array(
                            'oid' => 'Chapter:2',
                            'attributes' => array(
                                'name' => 'Chapter 2'
                            )
                        ),
                        array(
                            'oid' => 'Chapter:3',
                            'attributes' => array(
                                'name' => 'Chapter 3'
                            )
                        )
                    )
                    )));

    $format = new JsonFormat();
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
    $this->assertEquals('Chapter:1', $chapters['Chapter:1']->getOID()->__toString());
    $this->assertEquals('Chapter:2', $chapters['Chapter:2']->getOID()->__toString());
    $this->assertEquals('Chapter:3', $chapters['Chapter:3']->getOID()->__toString());
  }

  public function testSerializeSimple() {
    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'string' => 'abc',
                'integer' => 123,
                'boolean' => true,
                'array' => array(
                    'string' => 'def',
                    'integer' => 456,
                    'boolean' => false,
                    'array' => array(
                        'string' => 'ghi',
                        'integer' => 789
                    )
                )
            ));

    $format = new JsonFormat();
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
    $book = new Book(new ObjectId('Book', array(123)));
    $book->setValue('title', 'Matrix - The Original');
    $book->setValue('modified', '2011-10-01 00:01:01');

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'Book:123' => $book,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
            ));

    $format = new JsonFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('Book:123', $data['oid']);
    $this->assertEquals('Book', $data['className']);
    $this->assertEquals(false, $data['isReference']);
    $this->assertEquals(1317420061, $data['lastChange']);

    $attributes = $data['attributes'];
    $this->assertTrue(is_array($attributes));
    $this->assertEquals('Matrix - The Original', $attributes['title']);
  }

  public function testSerializeNodeHierarchy() {
    $book = new Book(new ObjectId('Book', array(123)));
    $book->setValue('title', 'Matrix - The Original');
    $book->setValue('modified', 1234567890);

    $chapter1 = new Chapter(new ObjectId('Chapter', array(1)));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter2 = new Chapter(new ObjectId('Chapter', array(2)));
    $chapter2->setValue('name', 'Chapter 2');
    $chapter3 = new Chapter(new ObjectId('Chapter', array(3)));
    $chapter3->setValue('name', 'Chapter 3');

    $image = new Image(new ObjectId('Image', array(12)));
    $image->setValue('file', 'image.png');

    $author = new Author(new ObjectId('Author', array(1)));
    $author->setValue('name', 'Unknown');

    $chapter1->addNode($chapter3, 'SubChapter');
    $chapter2->addNode($image, 'NormalImage');
    $chapter2->addNode($author, 'Author');

    $book->addNode($chapter1, 'Chapter');
    $book->addNode($chapter2, 'Chapter');

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'Book:123' => $book,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
        ));

    $format = new JsonFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('Book:123', $data['oid']);
    $this->assertEquals('Book', $data['className']);
    $this->assertEquals(false, $data['isReference']);

    $bookAttributes = $data['attributes'];
    $this->assertTrue(is_array($bookAttributes));
    $this->assertEquals('Matrix - The Original', $bookAttributes['title']);

    $chapters = $bookAttributes['Chapter'];
    $this->assertTrue(is_array($chapters));
    $this->assertEquals(2, sizeof($chapters));

    $chapter1 = $chapters[0];
    $this->assertEquals('Chapter:1', $chapter1['oid']);
    $chapter1Attributes = $chapter1['attributes'];
    $this->assertEquals('Chapter 1', $chapter1Attributes['name']);

    $subChapters = $chapter1Attributes['SubChapter'];
    $this->assertEquals(1, sizeof($subChapters));
    $subChapter = $subChapters[0];
    $this->assertEquals('Chapter:3', $subChapter['oid']);
    $subChapterAttributes = $subChapter['attributes'];
    $this->assertEquals('Chapter 3', $subChapterAttributes['name']);

    $chapter2 = $chapters[1];
    $this->assertEquals('Chapter:2', $chapter2['oid']);
    $chapter2Attributes = $chapter2['attributes'];
    $this->assertEquals('Chapter 2', $chapter2Attributes['name']);

    $author = $chapter2Attributes['Author'];
    $this->assertEquals('Author:1', $author['oid']);
    $authorAttributes = $author['attributes'];
    $this->assertEquals('Unknown', $authorAttributes['name']);
  }

  public function testSerializeNodeList() {
    $chapter1 = new Chapter(new ObjectId('Chapter', array(1)));
    $chapter1->setValue('name', 'Chapter 1');
    $chapter2 = new Chapter(new ObjectId('Chapter', array(2)));
    $chapter2->setValue('name', 'Chapter 2');
    $chapter3 = new Chapter(new ObjectId('Chapter', array(3)));
    $chapter3->setValue('name', 'Chapter 3');

    $list = array('type' => 'Chapter',
        'content' => array('contentType' => 'Chapter', $chapter1, $chapter2, $chapter3));

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => $list));

    $format = new JsonFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $list = $data['list'];
    $this->assertTrue(is_array($list));
    $this->assertEquals(2, sizeof(array_keys($list)));

    $chapters = $list['content'];
    $this->assertEquals(4, sizeof($chapters));
    $this->assertEquals('Chapter', $chapters['contentType']);

    $this->assertEquals('Chapter', $chapters[0]['className']);
    $this->assertEquals('Chapter:1', $chapters[0]['oid']);
    $this->assertEquals('Chapter', $chapters[1]['className']);
    $this->assertEquals('Chapter:2', $chapters[1]['oid']);
    $this->assertEquals('Chapter', $chapters[2]['className']);
    $this->assertEquals('Chapter:3', $chapters[2]['oid']);
  }
}
?>