<?php
require_once(WCMF_BASE . "wcmf/lib/presentation/class.Request.php");
require_once(WCMF_BASE . "wcmf/lib/presentation/class.Response.php");
require_once(WCMF_BASE . "wcmf/lib/presentation/format/class.JSONFormat.php");
require_once(WCMF_BASE . "test/lib/WCMFTestCase.php");
require_once(WCMF_BASE . "application/include/model/class.Document.php");

class JSONFormatTest extends WCMFTestCase {

  public function testDeserializeSimple() {
    $this->runAnonymous(true);
    $message = new Request('TerminateController', '', 'update');
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
    $message = new Request('TerminateController', '', 'update');
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
    $this->runAnonymous(false);
  }

  public function testDeserializeNodeHierarchy() {
    $this->runAnonymous(true);
    $message = new Request('TerminateController', '', 'update');
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
    $message = new Request('TerminateController', '', 'update');
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

  public function testSerializeNodeSimple() {
    $this->runAnonymous(true);

    $node = new Document();
    $node->setOID(new ObjectId('Document', array(123)));
    $node->setValue('title', 'Matrix - The Original');
    $message = new Response('TerminateController', '', 'update');
    $message->setValues(array(
                'Document:123' => $node,
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

    $attributes = $data['attributes'];
    $this->assertTrue(is_array($attributes));
    $this->assertTrue($attributes['title'] === 'Matrix - The Original');
  }

}

?>