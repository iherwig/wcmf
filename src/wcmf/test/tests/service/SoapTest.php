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
namespace wcmf\test\tests\service;

use wcmf\lib\service\SoapClient;
use wcmf\lib\util\TestUtil;
use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

/**
 * SoapTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapTest extends DatabaseTestCase {

  protected static function getEndPoint() {
    return "http://".TEST_SERVER."/soap";
  }

  protected function getDataSet() {
    return new ArrayDataSet([
      'DBSequence' => [
        ['table' => ''],
      ],
      'User' => [
        ['id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm', 'active' => 1, 'super_user' => 1, 'config' => ''],
      ],
      'NMUserRole' => [
        ['fk_user_id' => 0, 'fk_role_id' => 0],
      ],
      'Role' => [
        ['id' => 0, 'name' => 'administrators'],
      ],
      'Publisher' => [
        ['id' => 200, 'name' => 'Test Publisher'],
      ],
      'NMPublisherAuthor' => [
        ['id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 202],
      ],
      'Author' => [
        ['id' => 202, 'name' => 'Test Author'],
      ]
    ]);
  }

  protected function setUp(): void {
    parent::setUp();
    TestUtil::startServer(WCMF_BASE.'app/public', WCMF_BASE.'wcmf/test/router.php');

    // log wsdl
    $wsdlUrl = self::getEndPoint().'?wsdl';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wsdlUrl);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $wsdl = curl_exec($ch);
    curl_close($ch);
    $this->getLogger(__CLASS__)->info($wsdl);
    $this->assertRegExp('/<\?xml/', $wsdl);
  }

  /**
   * @return never
   */
  public function testSearch() {
    $this->markTestIncomplete('Relies on lucene search.');

    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $params = ['query' => 'Test'];
    $result = $client->call("search", $params);
    $this->assertFalse($result instanceof \SoapFault);
    $this->assertEquals(1, sizeof($result));
  }

  public function testList() {
    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $result = $client->call("getAuthorList");
    $this->assertFalse($result instanceof \SoapFault);
    $list = $result->list;
    $this->assertEquals(1, $result->totalCount);
    $this->assertEquals(1, sizeof($list));
    $this->assertEquals('app.src.model.Author:202', $list[0]->oid);
    $this->assertEquals('Test Author', $list[0]->name);
  }

  public function testRead() {
    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $params = ['oid' => 'app.src.model.Author:202', 'depth' => 1];
    $result = $client->call("readAuthor", $params);

    $this->assertFalse($result instanceof \SoapFault);
    $this->assertEquals('app.src.model.Author:202', $result->oid);
    $this->assertEquals('Test Author', $result->name);

    $publisherList = $result->Publisher;
    $this->assertEquals(1, sizeof($publisherList));

    $publisher = $publisherList[0];
    $this->assertEquals('app.src.model.Publisher:200', $publisher->oid);
    $this->assertEquals('Test Publisher', $publisher->name);
  }

  public function testUpdate() {
    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = 'app.src.model.Author:202';
    $author->id = 202;
    $author->name = 'Test Author Modified';
    $author->created = (new \DateTime())->format('Y-m-d H:i:s');
    $author->creator = '';
    $author->modified = (new \DateTime())->format('Y-m-d H:i:s');
    $author->last_editor = '';
    $author->Chapter = [];
    $author->Publisher = [];

    $params = ['Author' => $author];
    $result = $client->call("updateAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('app.src.model.Author:202', $result->oid);
    $this->assertEquals('Test Author Modified', $result->name);

    // test read updated object
    $oid = $result->oid;
    $params2 = ['oid' => $oid, 'depth' => 0];
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertEquals($oid, $result2->oid);
    $this->assertEquals('Test Author Modified', $result2->name);
  }

  public function testCreate() {
    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = 'app.src.model.Author:wcmfa3934eb734bd3ebfbd674da8d6bcd7c9';
    $author->id = '';
    $author->name = 'Test Author New';
    $author->created = (new \DateTime())->format('Y-m-d H:i:s');
    $author->creator = '';
    $author->modified = (new \DateTime())->format('Y-m-d H:i:s');
    $author->last_editor = '';
    $author->Chapter = [];
    $author->Publisher = [];

    $params = ['Author' => $author];
    $result = $client->call("createAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('Test Author New', $result->name);

    // test read new object
    $oid = $result->oid;
    $params2 = ['oid' => $oid, 'depth' => 0];
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertEquals($oid, $result2->oid);
    $this->assertEquals('Test Author New', $result2->name);
  }

  public function testDelete() {
    $options = ['trace' => 1, 'exceptions' => 1];
    $client = new SoapClient(self::getEndPoint().'?wsdl', 'admin', 'admin', $options);

    $params = ['oid' => 'app.src.model.Author:202'];
    $result = $client->call("deleteAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('app.src.model.Author:202', $result);

    // test read deleted object
    $oid = $result;
    $params2 = ['oid' => $oid, 'depth' => 0];
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertFalse(isset($result2->oid));
  }
}
?>