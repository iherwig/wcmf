<?php
require_once(WCMF_BASE . "wcmf/lib/presentation/class.Request.php");
require_once(WCMF_BASE . "wcmf/lib/presentation/class.Response.php");
require_once(WCMF_BASE . "wcmf/lib/presentation/format/class.JSONFormat.php");
require_once(WCMF_BASE . "test/lib/WCMFTestCase.php");
require_once(WCMF_BASE . "application/include/model/class.Document.php");

class JSONFormatTest extends WCMFTestCase {

  public function testDeserializeSimple() {
    $this->runAnonymous(true);
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

    $this->assertTrue($data['string'] === 'abc');
    $this->assertTrue($data['integer'] === 123);
    $this->assertTrue($data['boolean'] === true);
    $this->assertTrue(is_array($data['array']));

    $array1 = $data['array'];
    $this->assertTrue($array1['string'] === 'def');
    $this->assertTrue($array1['integer'] === 456);
    $this->assertTrue($array1['boolean'] === false);
    $this->assertTrue(is_array($array1['array']));

    $array2 = $array1['array'];
    $this->assertTrue($array2['string'] === 'ghi');
    $this->assertTrue($array2['integer'] === 789);
    $this->runAnonymous(false);
  }

  public function testDeserializeNodeSimple() {
    $this->runAnonymous(true);
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
    $this->assertTrue($document->getOID()->__toString() === 'Document:123');
    $this->assertTrue($document->getValue('title') === 'Matrix - The Original');

    $this->assertTrue($data['sid'] === 'cd65fec9bce4d7ec74e341a9031f8966');
    $this->assertFalse(isset($data['attributes']));
    $this->assertFalse(isset($data['oid']));
    $this->assertFalse(isset($data['lastChange']));
    $this->runAnonymous(false);
  }

  public function testDeserializeNodeHierarchy() {
    $this->runAnonymous(true);
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
    $this->assertTrue($document->getOID()->__toString() === 'Document:123');

    $pages = $document->getValue('Page');
    $this->assertTrue(sizeof($pages) == 2);

    $page1 = $pages[0];
    $this->assertTrue($page1->getOID()->__toString() === 'Page:1');

    $childPages = $page1->getValue('ChildPage');
    $this->assertTrue(sizeof($childPages) == 1);
    $this->assertTrue($childPages[0]->getOID()->__toString() === 'Page:3');

    $page2 = $pages[1];
    $this->assertTrue($page2->getOID()->__toString() === 'Page:2');

    $author = $page2->getValue('Author');
    $this->assertTrue($author->getOID()->__toString() === 'Author:1');
    $this->runAnonymous(false);
  }

  public function testDeserializeNodeList() {
    $this->runAnonymous(true);
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
    $this->assertTrue(sizeof(array_keys($list)) == 2);

    $pages = $list['content'];
    $this->assertTrue(sizeof($pages) == 4);
    $this->assertTrue($pages['contentType'] === 'Page');
    $this->assertTrue($pages['Page:1']->getOID()->__toString() === 'Page:1');
    $this->assertTrue($pages['Page:2']->getOID()->__toString() === 'Page:2');
    $this->assertTrue($pages['Page:3']->getOID()->__toString() === 'Page:3');
    $this->runAnonymous(false);
  }

  public function testSerializeSimple() {
    $this->runAnonymous(true);
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

    $this->assertTrue($data['string'] === 'abc');
    $this->assertTrue($data['integer'] === 123);
    $this->assertTrue($data['boolean'] === true);
    $this->assertTrue(is_array($data['array']));

    $array1 = $data['array'];
    $this->assertTrue($array1['string'] === 'def');
    $this->assertTrue($array1['integer'] === 456);
    $this->assertTrue($array1['boolean'] === false);
    $this->assertTrue(is_array($array1['array']));

    $array2 = $array1['array'];
    $this->assertTrue($array2['string'] === 'ghi');
    $this->assertTrue($array2['integer'] === 789);
    $this->runAnonymous(false);
  }

  public function testSerializeNodeSimple() {
    $this->runAnonymous(true);

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
    $this->assertTrue($data['sid'] === 'cd65fec9bce4d7ec74e341a9031f8966');
    $this->assertTrue($data['oid'] === 'Document:123');
    $this->assertTrue($data['className'] === 'Document');
    $this->assertTrue($data['isReference'] === false);
    $this->assertTrue($data['lastChange'] === 1317420061);

    $attributes = $data['attributes'];
    $this->assertTrue(is_array($attributes));
    $this->assertTrue($attributes['title'] === 'Matrix - The Original');
  }

  public function testSerializeNodeHierarchy() {
    $this->runAnonymous(true);

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
    $this->assertTrue($data['sid'] === 'cd65fec9bce4d7ec74e341a9031f8966');
    $this->assertTrue($data['oid'] === 'Document:123');
    $this->assertTrue($data['className'] === 'Document');
    $this->assertTrue($data['isReference'] === false);

    $documentAttributes = $data['attributes'];
    $this->assertTrue(is_array($documentAttributes));
    $this->assertTrue($documentAttributes['title'] === 'Matrix - The Original');

    $pages = $documentAttributes['Page'];
    $this->assertTrue(is_array($pages));
    $this->assertTrue(sizeof($pages) == 2);

    $page1 = $pages[0];
    $this->assertTrue($page1['oid'] === 'Page:1');
    $page1Attributes = $page1['attributes'];
    $this->assertTrue($page1Attributes['name'] === 'Page 1');

    $childPages = $page1Attributes['ChildPage'];
    $this->assertTrue(sizeof($childPages) == 1);
    $childPage = $childPages[0];
    $this->assertTrue($childPage['oid'] === 'Page:3');
    $childPageAttributes = $childPage['attributes'];
    $this->assertTrue($childPageAttributes['name'] === 'Page 3');

    $page2 = $pages[1];
    $this->assertTrue($page2['oid'] === 'Page:2');
    $page2Attributes = $page2['attributes'];
    $this->assertTrue($page2Attributes['name'] === 'Page 2');

    $author = $page2Attributes['Author'];
    $this->assertTrue($author['oid'] === 'Author:1');
    $authorAttributes = $author['attributes'];
    $this->assertTrue($authorAttributes['name'] === 'Unknown');
  }

  public function testSerializeNodeList() {
    $this->runAnonymous(true);

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
    $this->assertTrue(sizeof(array_keys($list)) == 2);

    $pages = $list['content'];
    $this->assertTrue(sizeof($pages) == 4);
    $this->assertTrue($pages['contentType'] === 'Page');

    $this->assertTrue($pages[0]['className'] === 'Page');
    $this->assertTrue($pages[0]['oid'] === 'Page:1');
    $this->assertTrue($pages[1]['className'] === 'Page');
    $this->assertTrue($pages[1]['oid'] === 'Page:2');
    $this->assertTrue($pages[2]['className'] === 'Page');
    $this->assertTrue($pages[2]['oid'] === 'Page:3');
    $this->runAnonymous(false);
  }
}

?>