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
namespace test\tests\service;

use test\lib\ArrayDataSet;
use test\lib\DatabaseTestCase;

use wcmf\lib\service\SoapClient;

/**
 * SoapTest.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class SoapTest extends DatabaseTestCase {

  const ENDPOINT = "http://localhost/wcmf/src/app/public/soap";

  protected function getDataSet() {
    return new ArrayDataSet(array(
      'dbsequence' => array(
        array('id' => 1),
      ),
      'user' => array(
        array('id' => 0, 'login' => 'admin', 'name' => 'Administrator', 'password' => '$2y$10$WG2E.dji.UcGzNZF2AlkvOb7158PwZpM2KxwkC6FJdKr4TQC9JXYm'),
      ),
      'nm_user_role' => array(
        array('fk_user_id' => 0, 'fk_role_id' => 0),
      ),
      'role' => array(
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
    $this->assertEquals('app.src.model.Author:202', $list[0]->oid);
    $this->assertEquals('Test Author', $list[0]->name);
  }

  public function testRead() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $params = array('oid' => 'app.src.model.Author:202', 'depth' => 1);
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
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = 'app.src.model.Author:202';
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

    $this->assertEquals('app.src.model.Author:202', $result->oid);
    $this->assertEquals('Test Author Modified', $result->name);
  }

  public function testCreate() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);

    $author = new \stdClass();
    $author->oid = '';
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
    echo $client->getDebugInfos();
    $this->assertFalse($result instanceof \SoapFault);

    $this->assertEquals('Test Author New', $result->name);
  }
}
?>