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

use new_roles\app\model\Author;
use new_roles\app\model\Document;
use new_roles\app\model\Image;
use new_roles\app\model\Page;

use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\JSONFormat;

/**
 * JSONFormatTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JSONFormatTest extends \PHPUnit_Framework_TestCase {

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

    $format = new JSONFormat();
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
                'oid' => 'Document:123',
                'lastChange' => 1234567890,
                'attributes' => array(
                    'title' => 'Matrix - The Original'
                )
            ));

    $format = new JSONFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $document = $data['Document:123'];
    $this->assertTrue($document instanceof Document);
    $this->assertEquals('Document:123', $document->getOID()->__toString());
    $this->assertEquals('Matrix - The Original', $document->getValue('title'));

    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertFalse(isset($data['attributes']));
    $this->assertFalse(isset($data['oid']));
    $this->assertFalse(isset($data['lastChange']));
  }

  public function testDeserializeNodeHierarchy() {
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'oid' => 'Document:123',
                'lastChange' => 1234567890,
                'attributes' => array(
                    'title' => 'Matrix - The Original',
                    'Page' => array(
                        array(
                            'oid' => 'Page:1',
                            'attributes' => array(
                                'name' => 'Page 1',
                                'ChildPage' => array(
                                    array(
                                        'oid' => 'Page:3',
                                        'attributes' => array(
                                            'name' => 'Page 3'
                                        )
                                    )
                                )
                            )
                        ),
                        array(
                            'oid' => 'Page:2',
                            'attributes' => array(
                                'name' => 'Page 2',
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

    $format = new JSONFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $document = $data['Document:123'];
    $this->assertTrue($document instanceof Document);
    $this->assertEquals('Document:123', $document->getOID()->__toString());

    $pages = $document->getValue('Page');
    $this->assertEquals(2, sizeof($pages));

    $page1 = $pages[0];
    $this->assertEquals('Page:1', $page1->getOID()->__toString());

    $childPages = $page1->getValue('ChildPage');
    $this->assertEquals(1, sizeof($childPages));
    $this->assertEquals('Page:3', $childPages[0]->getOID()->__toString());

    $page2 = $pages[1];
    $this->assertEquals('Page:2', $page2->getOID()->__toString());

    $author = $page2->getValue('Author');
    $this->assertEquals('Author:1', $author->getOID()->__toString());
  }

  public function testDeserializeNodeList() {
    $message = new Request('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => array(
                    'type' => 'Page',
                    'content' => array(
                        'contentType' => 'Page',
                        array(
                            'oid' => 'Page:1',
                            'attributes' => array(
                                'name' => 'Page 1'
                            )
                        ),
                        array(
                            'oid' => 'Page:2',
                            'attributes' => array(
                                'name' => 'Page 2'
                            )
                        ),
                        array(
                            'oid' => 'Page:3',
                            'attributes' => array(
                                'name' => 'Page 3'
                            )
                        )
                    )
                    )));

    $format = new JSONFormat();
    $format->deserialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $list = $data['list'];
    $this->assertTrue(is_array($list));
    $this->assertEquals(2, sizeof(array_keys($list)));

    $pages = $list['content'];
    $this->assertEquals(4, sizeof($pages));
    $this->assertEquals('Page', $pages['contentType']);
    $this->assertEquals('Page:1', $pages['Page:1']->getOID()->__toString());
    $this->assertEquals('Page:2', $pages['Page:2']->getOID()->__toString());
    $this->assertEquals('Page:3', $pages['Page:3']->getOID()->__toString());
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

    $format = new JSONFormat();
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
    $document = new Document(new ObjectId('Document', array(123)));
    $document->setValue('title', 'Matrix - The Original');
    $document->setValue('modified', '2011-10-01 00:01:01');

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'Document:123' => $document,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
            ));

    $format = new JSONFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('Document:123', $data['oid']);
    $this->assertEquals('Document', $data['className']);
    $this->assertEquals(false, $data['isReference']);
    $this->assertEquals(1317420061, $data['lastChange']);

    $attributes = $data['attributes'];
    $this->assertTrue(is_array($attributes));
    $this->assertEquals('Matrix - The Original', $attributes['title']);
  }

  public function testSerializeNodeHierarchy() {
    $document = new Document(new ObjectId('Document', array(123)));
    $document->setValue('title', 'Matrix - The Original');
    $document->setValue('modified', 1234567890);

    $page1 = new Page(new ObjectId('Page', array(1)));
    $page1->setValue('name', 'Page 1');
    $page2 = new Page(new ObjectId('Page', array(2)));
    $page2->setValue('name', 'Page 2');
    $page3 = new Page(new ObjectId('Page', array(3)));
    $page3->setValue('name', 'Page 3');

    $image = new Image(new ObjectId('Image', array(12)));
    $image->setValue('file', 'image.png');

    $author = new Author(new ObjectId('Author', array(1)));
    $author->setValue('name', 'Unknown');

    $page1->addNode($page3, 'ChildPage');
    $page2->addNode($image, 'NormalImage');
    $page2->addNode($author, 'Author');

    $document->addNode($page1, 'Page');
    $document->addNode($page2, 'Page');

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'Document:123' => $document,
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966'
        ));

    $format = new JSONFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));
    $this->assertEquals('cd65fec9bce4d7ec74e341a9031f8966', $data['sid']);
    $this->assertEquals('Document:123', $data['oid']);
    $this->assertEquals('Document', $data['className']);
    $this->assertEquals(false, $data['isReference']);

    $documentAttributes = $data['attributes'];
    $this->assertTrue(is_array($documentAttributes));
    $this->assertEquals('Matrix - The Original', $documentAttributes['title']);

    $pages = $documentAttributes['Page'];
    $this->assertTrue(is_array($pages));
    $this->assertEquals(2, sizeof($pages));

    $page1 = $pages[0];
    $this->assertEquals('Page:1', $page1['oid']);
    $page1Attributes = $page1['attributes'];
    $this->assertEquals('Page 1', $page1Attributes['name']);

    $childPages = $page1Attributes['ChildPage'];
    $this->assertEquals(1, sizeof($childPages));
    $childPage = $childPages[0];
    $this->assertEquals('Page:3', $childPage['oid']);
    $childPageAttributes = $childPage['attributes'];
    $this->assertEquals('Page 3', $childPageAttributes['name']);

    $page2 = $pages[1];
    $this->assertEquals('Page:2', $page2['oid']);
    $page2Attributes = $page2['attributes'];
    $this->assertEquals('Page 2', $page2Attributes['name']);

    $author = $page2Attributes['Author'];
    $this->assertEquals('Author:1', $author['oid']);
    $authorAttributes = $author['attributes'];
    $this->assertEquals('Unknown', $authorAttributes['name']);
  }

  public function testSerializeNodeList() {
    $page1 = new Page(new ObjectId('Page', array(1)));
    $page1->setValue('name', 'Page 1');
    $page2 = new Page(new ObjectId('Page', array(2)));
    $page2->setValue('name', 'Page 2');
    $page3 = new Page(new ObjectId('Page', array(3)));
    $page3->setValue('name', 'Page 3');

    $list = array('type' => 'Page',
        'content' => array('contentType' => 'Page', $page1, $page2, $page3));

    $message = new Response('controller', 'context', 'action');
    $message->setValues(array(
                'sid' => 'cd65fec9bce4d7ec74e341a9031f8966',
                'list' => $list));

    $format = new JSONFormat();
    $format->serialize($message);

    // test
    $data = $message->getValues();
    $this->assertTrue(is_array($data));

    $list = $data['list'];
    $this->assertTrue(is_array($list));
    $this->assertEquals(2, sizeof(array_keys($list)));

    $pages = $list['content'];
    $this->assertEquals(4, sizeof($pages));
    $this->assertEquals('Page', $pages['contentType']);

    $this->assertEquals('Page', $pages[0]['className']);
    $this->assertEquals('Page:1', $pages[0]['oid']);
    $this->assertEquals('Page', $pages[1]['className']);
    $this->assertEquals('Page:2', $pages[1]['oid']);
    $this->assertEquals('Page', $pages[2]['className']);
    $this->assertEquals('Page:3', $pages[2]['oid']);
  }
}
?>