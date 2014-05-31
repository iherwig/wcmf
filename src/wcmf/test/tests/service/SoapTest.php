<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\test\tests\service;

use wcmf\test\lib\ArrayDataSet;
use wcmf\test\lib\DatabaseTestCase;

use wcmf\lib\service\SoapClient;

/**
 * SoapTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapTest extends DatabaseTestCase {

  const ENDPOINT = "http://localhost/wcmf/src/wcmf/test/app/public/soap.php";

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'DBSequence' => array(
        array('id' => 1),
      ),
      'User' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm'),
      ),
      'NMUserRole' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'Role' => array(
        array('id' => 0, 'name' => 'administrators'),
      ),
      'Publisher' => array(
        array('id' => 200, 'name' => 'Test Publisher'),
      ),
      'NMPublisherAuthor' => array(
        array('id' => 201, 'fk_publisher_id' => 200, 'fk_author_id' => 202),
      ),
      'Author' => array(
        array('id' => 202, 'name' => 'Test Author'),
      )
    ));
  }

  public function testSearch() {
    $this->markTestIncomplete('Relies on lucene search.');

    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $params = array('query' => 'Test');
    $result = $client->call("search", $params);
    $this->assertFalse($result instanceof \SoapFault);
    $this->assertEquals(1, sizeof($result));
  }

  public function testList() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $result = $client->call("getAuthorList");
    $this->assertFalse($result instanceof \SoapFault);
    $list = $result->list;
    $this->assertEquals(1, $result->totalCount);
    $this->assertEquals(1, sizeof($list));
    $this->assertEquals('wcmf.test.app.src.model.Author:202', $list[0]->oid);
    $this->assertEquals('Test Author', $list[0]->name);
  }

  public function testRead() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $params = array('oid' => 'wcmf.test.app.src.model.Author:202', 'depth' => 1);
    $result = $client->call("readAuthor", $params);

    $this->assertFalse($result instanceof \SoapFault);
    $this->assertEquals('wcmf.test.app.src.model.Author:202', $result->oid);
    $this->assertEquals('Test Author', $result->name);

    $publisherList = $result->Publisher;
    $this->assertEquals(1, sizeof($publisherList));

    $publisher = $publisherList[0];
    $this->assertEquals('wcmf.test.app.src.model.Publisher:200', $publisher->oid);
    $this->assertEquals('Test Publisher', $publisher->name);
  }

  public function testUpdate() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = 'wcmf.test.app.src.model.Author:202';
    $author->id = 202;
    $author->name = 'Test Author Modified';
    $author->created = '';
    $author->creator = '';
    $author->modified = '';
    $author->last_editor = '';
    $author->Chapter = array();
    $author->Publisher = array();

    $params = array('Author' => $author);
    $result = $client->call("updateAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('wcmf.test.app.src.model.Author:202', $result->oid);
    $this->assertEquals('Test Author Modified', $result->name);

    // test read updated object
    $oid = $result->oid;
    $params2 = array('oid' => $oid, 'depth' => 0);
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertEquals($oid, $result2->oid);
    $this->assertEquals('Test Author Modified', $result2->name);
  }

  public function testCreate() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = 'wcmf.test.app.src.model.Author:wcmfa3934eb734bd3ebfbd674da8d6bcd7c9';
    $author->id = '';
    $author->name = 'Test Author New';
    $author->created = '';
    $author->creator = '';
    $author->modified = '';
    $author->last_editor = '';
    $author->Chapter = array();
    $author->Publisher = array();

    $params = array('Author' => $author);
    $result = $client->call("createAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('Test Author New', $result->name);

    // test read new object
    $oid = $result->oid;
    $params2 = array('oid' => $oid, 'depth' => 0);
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertEquals($oid, $result2->oid);
    $this->assertEquals('Test Author New', $result2->name);
  }

  public function testDelete() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $params = array('oid' => 'wcmf.test.app.src.model.Author:202');
    $result = $client->call("deleteAuthor", $params);
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('wcmf.test.app.src.model.Author:202', $result);

    // test read deleted object
    $oid = $result;
    $params2 = array('oid' => $oid, 'depth' => 0);
    $result2 = $client->call("readAuthor", $params2);

    $this->assertFalse($result2 instanceof \SoapFault);
    $this->assertEquals('O:8:"stdClass":0:{}', serialize($result2));
  }
}
?>