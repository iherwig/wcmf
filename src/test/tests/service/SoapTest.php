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
      'Author' => array(
        array('id' => 12345, 'name' => 'Test Author'),
      )
    ));
  }

  public function testSearch() {
    $this->markTestIncomplete('Relies on lucene search.');

    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);
    $params = array('query' => 'Test');
    $result = $client->call("search", $params);
    $this->assertEquals(1, sizeof($result));
  }

  public function testList() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);
    $result = $client->call("getAuthorList");
    $list = $result->list;
    $this->assertEquals(1, $result->totalCount);
    $this->assertEquals(1, sizeof($list));
    $this->assertEquals('app.src.model.Author:12345', $list[0]->oid);
    $this->assertEquals('Test Author', $list[0]->name);
  }

  public function testRead() {
    $options = array('trace' => 1, 'exceptions' => 0);
    $client = new SoapClient(self::ENDPOINT.'?wsdl', 'admin', 'admin', $options);
    $params = array('oid' => 'app.src.model.Author:12345', 'depth' => 1);
    $result = $client->call("readAuthor", $params);
    $this->assertEquals('app.src.model.Author:12345', $result->oid);
    $this->assertEquals('Test Author', $result->name);
  }
}
?>